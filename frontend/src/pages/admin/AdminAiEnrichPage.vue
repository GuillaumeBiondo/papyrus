<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { adminService } from '@/services/admin.service'
import { projectsService } from '@/services/projects.service'
import type { AiEnrichType, AiStats, ContentType } from '@/types'

// ── Tab ───────────────────────────────────────────────────────
const activeTab = ref<'config' | 'stats'>('config')

// ── Types de contenu ──────────────────────────────────────────
const contentTypes = ref<Pick<ContentType, 'id' | 'name' | 'short_name' | 'slug'>[]>([])

// ── Types (config) ────────────────────────────────────────────
const types        = ref<AiEnrichType[]>([])
const loadingConfig = ref(true)

const showForm  = ref(false)
const editingId = ref<number | null>(null)
const saving    = ref(false)
const error     = ref('')

const form = ref<Partial<AiEnrichType>>({
  type_key: '', label: '', description: '', is_active: true, system_prompt: '', allowed_content_types: null,
})

function resetForm() {
  editingId.value = null
  form.value = { type_key: '', label: '', description: '', is_active: true, system_prompt: '', allowed_content_types: null }
}

function startCreate() { resetForm(); showForm.value = true }

function startEdit(t: AiEnrichType) {
  editingId.value = t.id
  form.value = { ...t }
  showForm.value = true
}

function cancelForm() { showForm.value = false; resetForm() }

async function save() {
  if (!form.value.label?.trim() || !form.value.system_prompt?.trim()) {
    error.value = 'Le label et le prompt système sont obligatoires.'
    return
  }
  if (editingId.value === null && !form.value.type_key?.trim()) {
    error.value = 'La clé technique est obligatoire.'
    return
  }
  error.value = ''
  saving.value = true
  try {
    if (editingId.value !== null) {
      const { type } = await adminService.updateAiEnrichType(editingId.value, form.value)
      const idx = types.value.findIndex(t => t.id === editingId.value)
      if (idx !== -1) types.value[idx] = type
    } else {
      const { type } = await adminService.createAiEnrichType(form.value)
      types.value.push(type)
    }
    showForm.value = false
    resetForm()
  } catch (err: unknown) {
    const axiosErr = err as { response?: { data?: { errors?: Record<string, string[]> } } }
    const errors   = axiosErr?.response?.data?.errors
    error.value    = errors ? Object.values(errors).flat().join(' ') : 'Erreur lors de la sauvegarde.'
  } finally {
    saving.value = false
  }
}

async function toggleActive(t: AiEnrichType) {
  t.is_active = !t.is_active
  await adminService.updateAiEnrichType(t.id, { is_active: t.is_active })
}

async function togglePremium(t: AiEnrichType) {
  t.is_premium = !t.is_premium
  await adminService.updateAiEnrichType(t.id, { is_premium: t.is_premium })
}

const confirmDeleteId = ref<number | null>(null)
async function confirmDelete(id: number) {
  await adminService.deleteAiEnrichType(id)
  types.value = types.value.filter(t => t.id !== id)
  confirmDeleteId.value = null
}

async function moveUp(t: AiEnrichType) {
  const idx = types.value.findIndex(x => x.id === t.id)
  if (idx <= 0) return
  const prev = types.value[idx - 1]!
  ;[prev.sort_order, t.sort_order] = [t.sort_order, prev.sort_order]
  types.value = [...types.value].sort((a, b) => a.sort_order - b.sort_order)
  await adminService.reorderAiEnrichTypes(types.value.map(x => x.id))
}

async function moveDown(t: AiEnrichType) {
  const idx = types.value.findIndex(x => x.id === t.id)
  if (idx >= types.value.length - 1) return
  const next = types.value[idx + 1]!
  ;[next.sort_order, t.sort_order] = [t.sort_order, next.sort_order]
  types.value = [...types.value].sort((a, b) => a.sort_order - b.sort_order)
  await adminService.reorderAiEnrichTypes(types.value.map(x => x.id))
}

// ── Stats ─────────────────────────────────────────────────────
const allStats     = ref<AiStats | null>(null)
const loadingStats = ref(false)
const statsLoaded  = ref(false)

const enrichStats = computed(() => {
  if (!allStats.value) return null
  const filtered = allStats.value.by_revision.filter(r => r.label.startsWith('Dico: '))
  const totalCalls  = filtered.reduce((s, r) => s + r.calls, 0)
  const totalTokens = filtered.reduce((s, r) => s + r.est_tokens, 0)
  return { rows: filtered, totalCalls, totalTokens }
})

async function loadStats() {
  if (statsLoaded.value) return
  loadingStats.value = true
  try {
    allStats.value = await adminService.getAiStats()
    statsLoaded.value = true
  } finally {
    loadingStats.value = false
  }
}

async function switchTab(tab: 'config' | 'stats') {
  activeTab.value = tab
  if (tab === 'stats') await loadStats()
}

function formatDate(iso: string | null) {
  if (!iso) return '—'
  return new Date(iso).toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: '2-digit' })
}

function formatNumber(n: number) {
  return new Intl.NumberFormat('fr-FR').format(n)
}

// ── Types de contenu ──────────────────────────────────────────
function toggleContentType(id: string) {
  const current = form.value.allowed_content_types ?? []
  const idx = current.indexOf(id)
  if (idx === -1) form.value.allowed_content_types = [...current, id]
  else {
    const next = current.filter(s => s !== id)
    form.value.allowed_content_types = next.length > 0 ? next : null
  }
}

// ── Chargement initial ────────────────────────────────────────
onMounted(async () => {
  try {
    const [{ types: list }, ctRes] = await Promise.all([
      adminService.getAiEnrichTypes(),
      projectsService.getActiveContentTypes(),
    ])
    types.value = list
    contentTypes.value = ctRes.content_types
  } finally {
    loadingConfig.value = false
  }
})
</script>

<template>
  <div class="p-6 max-w-4xl mx-auto space-y-5">
    <!-- En-tête + tabs -->
    <div class="flex items-center justify-between flex-wrap gap-3">
      <div>
        <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-100">Dictionnaire IA</h1>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
          Types d'enrichissement lexical — configuration et statistiques d'usage.
        </p>
      </div>
      <div class="flex items-center gap-1 bg-gray-100 dark:bg-gray-800 rounded-lg p-1">
        <button
          v-for="tab in [{ key: 'config', label: 'Configuration' }, { key: 'stats', label: 'Statistiques' }]"
          :key="tab.key"
          class="px-3 py-1.5 rounded-md text-sm transition-colors"
          :class="activeTab === tab.key
            ? 'bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm font-medium'
            : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'"
          @click="switchTab(tab.key as 'config' | 'stats')"
        >
          {{ tab.label }}
        </button>
      </div>
    </div>

    <!-- ══ TAB CONFIG ══ -->
    <template v-if="activeTab === 'config'">
      <div class="flex justify-end">
        <button
          class="flex items-center gap-2 px-3 py-1.5 rounded-lg text-sm bg-indigo-600 hover:bg-indigo-700 text-white transition-colors"
          @click="startCreate"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
          </svg>
          Ajouter un type
        </button>
      </div>

      <div v-if="loadingConfig" class="text-sm text-gray-400">Chargement…</div>

      <div v-else-if="types.length" class="space-y-2">
        <div
          v-for="(t, idx) in types"
          :key="t.id"
          class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-4 flex items-start gap-4"
        >
          <!-- Réordonnancement -->
          <div class="flex flex-col gap-0.5 pt-0.5 shrink-0">
            <button
              class="p-0.5 rounded text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 disabled:opacity-30 transition-colors"
              :disabled="idx === 0"
              @click="moveUp(t)"
            >
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/>
              </svg>
            </button>
            <button
              class="p-0.5 rounded text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 disabled:opacity-30 transition-colors"
              :disabled="idx === types.length - 1"
              @click="moveDown(t)"
            >
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
              </svg>
            </button>
          </div>

          <div class="flex-1 min-w-0 space-y-1.5">
            <div class="flex items-center gap-2 flex-wrap">
              <span class="font-medium text-gray-900 dark:text-gray-100">{{ t.label }}</span>
              <code class="text-xs px-1.5 py-0.5 rounded bg-gray-100 dark:bg-gray-800 text-gray-500 dark:text-gray-400 font-mono">{{ t.type_key }}</code>
              <span
                v-if="t.allowed_content_types?.length"
                class="text-xs px-1.5 py-0.5 rounded bg-teal-50 dark:bg-teal-950 text-teal-600 dark:text-teal-400"
              >
                Types : {{ t.allowed_content_types.map(id => contentTypes.find(c => c.id === id)?.short_name || contentTypes.find(c => c.id === id)?.name || id).join(', ') }}
              </span>
            </div>
            <p v-if="t.description" class="text-xs text-gray-500 dark:text-gray-400 italic">{{ t.description }}</p>
            <p class="text-xs text-gray-400 dark:text-gray-500 line-clamp-2 font-mono">{{ t.system_prompt }}</p>
          </div>

          <div class="flex items-center gap-2 shrink-0">
            <button
              :title="t.is_premium ? 'Premium — cliquer pour rendre gratuit' : 'Gratuit — cliquer pour réserver au premium'"
              :class="['w-9 h-5 rounded-full transition-colors relative', t.is_premium ? 'bg-amber-500' : 'bg-gray-300 dark:bg-gray-700']"
              @click="togglePremium(t)"
            >
              <span :class="['absolute left-0 top-0.5 w-4 h-4 rounded-full bg-white shadow transition-transform', t.is_premium ? 'translate-x-4' : 'translate-x-0.5']"/>
            </button>
            <button
              :title="t.is_active ? 'Désactiver' : 'Activer'"
              :class="['w-9 h-5 rounded-full transition-colors relative', t.is_active ? 'bg-indigo-500' : 'bg-gray-300 dark:bg-gray-700']"
              @click="toggleActive(t)"
            >
              <span :class="['absolute left-0 top-0.5 w-4 h-4 rounded-full bg-white shadow transition-transform', t.is_active ? 'translate-x-4' : 'translate-x-0.5']"/>
            </button>
            <button
              class="p-1.5 rounded text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
              title="Modifier"
              @click="startEdit(t)"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
              </svg>
            </button>
            <button
              v-if="confirmDeleteId !== t.id"
              class="p-1.5 rounded text-gray-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-950 transition-colors"
              title="Supprimer"
              @click="confirmDeleteId = t.id"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
              </svg>
            </button>
            <div v-else class="flex items-center gap-1">
              <span class="text-xs text-red-500">Confirmer ?</span>
              <button class="text-xs text-red-500 font-medium hover:underline" @click="confirmDelete(t.id)">Oui</button>
              <button class="text-xs text-gray-400 hover:underline" @click="confirmDeleteId = null">Non</button>
            </div>
          </div>
        </div>
      </div>
      <p v-else class="text-sm text-gray-400">Aucun type configuré.</p>
    </template>

    <!-- ══ TAB STATS ══ -->
    <template v-else>
      <div v-if="loadingStats" class="text-sm text-gray-400 py-8 text-center">Chargement des statistiques…</div>

      <template v-else-if="enrichStats">
        <!-- Totaux -->
        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
          <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-4">
            <p class="text-xs text-gray-500 dark:text-gray-400">Appels totaux</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-gray-100 mt-1">{{ formatNumber(enrichStats.totalCalls) }}</p>
          </div>
          <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-4">
            <p class="text-xs text-gray-500 dark:text-gray-400">Tokens estimés</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-gray-100 mt-1">{{ formatNumber(enrichStats.totalTokens) }}</p>
            <p class="text-xs text-gray-400 mt-0.5">≈ chars / 4</p>
          </div>
          <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-4">
            <p class="text-xs text-gray-500 dark:text-gray-400">Types actifs</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-gray-100 mt-1">{{ types.filter(t => t.is_active).length }}</p>
            <p class="text-xs text-gray-400 mt-0.5">sur {{ types.length }} configurés</p>
          </div>
        </div>

        <!-- Par type -->
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl overflow-hidden">
          <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-800">
            <p class="text-sm font-medium text-gray-900 dark:text-gray-100">Par type d'enrichissement</p>
          </div>
          <div v-if="enrichStats.rows.length" class="divide-y divide-gray-100 dark:divide-gray-800">
            <div
              v-for="r in enrichStats.rows"
              :key="r.label"
              class="px-4 py-3 flex items-center gap-4"
            >
              <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ r.label.replace('Dico: ', '') }}</p>
                <p class="text-xs text-gray-400 mt-0.5">Dernier usage : {{ formatDate(r.last_used_at) }}</p>
              </div>
              <div class="flex items-center gap-6 text-right shrink-0">
                <div>
                  <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ formatNumber(r.calls) }}</p>
                  <p class="text-xs text-gray-400">appels</p>
                </div>
                <div>
                  <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ formatNumber(r.avg_chars) }}</p>
                  <p class="text-xs text-gray-400">chars moy.</p>
                </div>
                <div>
                  <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ r.avg_changes }}</p>
                  <p class="text-xs text-gray-400">items moy.</p>
                </div>
                <div>
                  <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ formatNumber(r.est_tokens) }}</p>
                  <p class="text-xs text-gray-400">tokens est.</p>
                </div>
              </div>
            </div>
          </div>
          <p v-else class="px-4 py-6 text-sm text-gray-400 text-center">Aucune utilisation enregistrée.</p>
        </div>

        <p class="text-xs text-gray-400">
          Statistiques filtrées sur les appels au dictionnaire IA uniquement.
          Estimation des tokens : chars / 4. Les tokens de sortie ne sont pas comptabilisés.
        </p>
      </template>

      <p v-else class="text-sm text-gray-400 py-8 text-center">Erreur lors du chargement.</p>
    </template>
  </div>

  <!-- ── Formulaire modal ── -->
  <Transition name="fade">
    <div
      v-if="showForm"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/40"
      @click.self="cancelForm"
    >
      <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-2xl w-full max-w-2xl mx-4 max-h-[90vh] overflow-y-auto">
        <div class="p-6 space-y-4">
          <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
            {{ editingId !== null ? 'Modifier le type' : 'Nouveau type d\'enrichissement' }}
          </h2>

          <p v-if="error" class="text-sm text-red-500">{{ error }}</p>

          <!-- Clé technique (création uniquement) -->
          <div v-if="editingId === null">
            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
              Clé technique <span class="text-red-400">*</span>
              <span class="text-gray-400 font-normal"> — minuscules, chiffres, underscores uniquement</span>
            </label>
            <input
              v-model="form.type_key"
              type="text"
              placeholder="ex : antonymes, etymologie, rimes…"
              class="w-full text-sm rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500 font-mono"
            />
          </div>
          <div v-else class="text-xs text-gray-500 dark:text-gray-400">
            Clé : <code class="bg-gray-100 dark:bg-gray-800 px-1.5 py-0.5 rounded font-mono">{{ form.type_key }}</code>
            <span class="ml-2 text-gray-400">(non modifiable après création)</span>
          </div>

          <div>
            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
              Label du bouton <span class="text-red-400">*</span>
            </label>
            <input
              v-model="form.label"
              type="text"
              placeholder="Ex : Synonymes, Métaphores, Antonymes…"
              class="w-full text-sm rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500"
            />
          </div>

          <div>
            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
              Description (infobulle au survol)
            </label>
            <input
              v-model="form.description"
              type="text"
              placeholder="Ex : 5 à 15 synonymes avec nuances de registre…"
              class="w-full text-sm rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500"
            />
          </div>

          <div>
            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
              Prompt système (instructions LLM) <span class="text-red-400">*</span>
            </label>
            <textarea
              v-model="form.system_prompt"
              rows="7"
              placeholder="Tu es un linguiste français. Réponds UNIQUEMENT en JSON avec la clé &quot;items&quot;…"
              class="w-full text-sm rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500 resize-y font-mono"
            />
            <p class="text-xs text-gray-400 mt-1">
              La réponse attendue est un objet JSON avec la clé <code class="bg-gray-100 dark:bg-gray-800 px-1 rounded">"items": [{"text": "...", "detail": "..."}]</code>.
              Le mot ou l'expression sélectionné sera fourni entre guillemets dans le message utilisateur.
            </p>
          </div>

          <!-- Types de contenu concernés -->
          <div v-if="contentTypes.length" class="space-y-2">
            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">
              Types de projet concernés
              <span class="text-gray-400 font-normal"> — laisser vide = tous les types</span>
            </label>
            <div class="flex flex-wrap gap-2">
              <label
                v-for="ct in contentTypes"
                :key="ct.id"
                class="flex items-center gap-1.5 text-sm cursor-pointer px-2.5 py-1 rounded-lg border transition-colors"
                :class="(form.allowed_content_types ?? []).includes(ct.id)
                  ? 'border-teal-400 bg-teal-50 dark:bg-teal-950 text-teal-700 dark:text-teal-300'
                  : 'border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:border-gray-300 dark:hover:border-gray-600'"
              >
                <input
                  type="checkbox"
                  class="accent-teal-500"
                  :checked="(form.allowed_content_types ?? []).includes(ct.id)"
                  @change="toggleContentType(ct.id)"
                />
                {{ ct.short_name || ct.name }}
              </label>
            </div>
          </div>

          <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300 cursor-pointer">
            <input type="checkbox" v-model="form.is_active" class="accent-indigo-500" />
            Actif (visible dans le sous-menu Dico)
          </label>

          <div class="flex justify-end gap-2 pt-2">
            <button
              class="px-4 py-2 text-sm rounded-lg text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
              @click="cancelForm"
            >
              Annuler
            </button>
            <button
              class="px-4 py-2 text-sm rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white transition-colors disabled:opacity-50"
              :disabled="saving"
              @click="save"
            >
              {{ saving ? 'Enregistrement…' : 'Enregistrer' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </Transition>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.15s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
.line-clamp-2 { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
</style>
