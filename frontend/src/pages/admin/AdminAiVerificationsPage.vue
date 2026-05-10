<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { adminService } from '@/services/admin.service'
import type { AiVerification } from '@/types'

const verifications = ref<AiVerification[]>([])
const loading = ref(true)
const saving  = ref(false)
const error   = ref('')

// ── Form ──────────────────────────────────────────────────────
const showForm   = ref(false)
const editingId  = ref<number | null>(null)
const form = ref<Partial<AiVerification>>({
  label: '',
  is_active: true,
  target: 'all',
  has_extra_input: false,
  extra_input_label: '',
  extra_input_placeholder: '',
  pre_prompt: '',
})

function resetForm() {
  editingId.value = null
  form.value = {
    label: '', is_active: true, target: 'all',
    has_extra_input: false, extra_input_label: '',
    extra_input_placeholder: '', pre_prompt: '',
  }
}

function startCreate() {
  resetForm()
  showForm.value = true
}

function startEdit(v: AiVerification) {
  editingId.value = v.id
  form.value = { ...v }
  showForm.value = true
}

function cancelForm() {
  showForm.value = false
  resetForm()
}

// ── Chargement ────────────────────────────────────────────────
onMounted(async () => {
  try {
    const { verifications: list } = await adminService.getAiVerifications()
    verifications.value = list
  } finally {
    loading.value = false
  }
})

// ── Sauvegarde ────────────────────────────────────────────────
async function save() {
  if (!form.value.label?.trim() || !form.value.pre_prompt?.trim()) {
    error.value = 'Le label et le pré-prompt sont obligatoires.'
    return
  }
  error.value = ''
  saving.value = true
  try {
    if (editingId.value !== null) {
      const { verification } = await adminService.updateAiVerification(editingId.value, form.value)
      const idx = verifications.value.findIndex(v => v.id === editingId.value)
      if (idx !== -1) verifications.value[idx] = verification
    } else {
      const { verification } = await adminService.createAiVerification(form.value)
      verifications.value.push(verification)
    }
    showForm.value = false
    resetForm()
  } catch {
    error.value = 'Une erreur est survenue lors de la sauvegarde.'
  } finally {
    saving.value = false
  }
}

// ── Toggle actif ──────────────────────────────────────────────
async function toggleActive(v: AiVerification) {
  v.is_active = !v.is_active
  await adminService.updateAiVerification(v.id, { is_active: v.is_active })
}

// ── Suppression ───────────────────────────────────────────────
const confirmDeleteId = ref<number | null>(null)

async function confirmDelete(id: number) {
  await adminService.deleteAiVerification(id)
  verifications.value = verifications.value.filter(v => v.id !== id)
  confirmDeleteId.value = null
}

// ── Réordonnement (flèches) ───────────────────────────────────
async function moveUp(v: AiVerification) {
  const idx = verifications.value.findIndex(x => x.id === v.id)
  if (idx <= 0) return
  const prev = verifications.value[idx - 1]!
  ;[prev.sort_order, v.sort_order] = [v.sort_order, prev.sort_order]
  verifications.value = [...verifications.value].sort((a, b) => a.sort_order - b.sort_order)
  await adminService.reorderAiVerifications(verifications.value.map(x => x.id))
}

async function moveDown(v: AiVerification) {
  const idx = verifications.value.findIndex(x => x.id === v.id)
  if (idx >= verifications.value.length - 1) return
  const next = verifications.value[idx + 1]!
  ;[next.sort_order, v.sort_order] = [v.sort_order, next.sort_order]
  verifications.value = [...verifications.value].sort((a, b) => a.sort_order - b.sort_order)
  await adminService.reorderAiVerifications(verifications.value.map(x => x.id))
}

const TARGET_LABEL: Record<AiVerification['target'], string> = {
  selection: 'Sélection uniquement',
  all: 'Tout le texte',
  both: 'Sélection ou tout le texte',
}
</script>

<template>
  <div class="p-6 max-w-4xl mx-auto space-y-6">
    <!-- En-tête -->
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-100">Vérifications IA</h1>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
          Configurez les boutons de vérification affichés dans la barre d'outils de l'éditeur.
        </p>
      </div>
      <button
        class="flex items-center gap-2 px-3 py-1.5 rounded-lg text-sm bg-brand-600 hover:bg-brand-700 text-white transition-colors"
        @click="startCreate"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        Ajouter
      </button>
    </div>

    <!-- Chargement -->
    <div v-if="loading" class="text-sm text-gray-400">Chargement…</div>

    <!-- Liste -->
    <div v-else-if="verifications.length" class="space-y-2">
      <div
        v-for="(v, idx) in verifications"
        :key="v.id"
        class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-4 flex items-start gap-4"
      >
        <!-- Flèches -->
        <div class="flex flex-col gap-0.5 pt-0.5 shrink-0">
          <button
            class="p-0.5 rounded text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 disabled:opacity-30 transition-colors"
            :disabled="idx === 0"
            @click="moveUp(v)"
          >
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/>
            </svg>
          </button>
          <button
            class="p-0.5 rounded text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 disabled:opacity-30 transition-colors"
            :disabled="idx === verifications.length - 1"
            @click="moveDown(v)"
          >
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
          </button>
        </div>

        <!-- Contenu -->
        <div class="flex-1 min-w-0 space-y-1.5">
          <div class="flex items-center gap-2 flex-wrap">
            <span class="font-medium text-gray-900 dark:text-gray-100">{{ v.label }}</span>
            <span class="text-xs px-1.5 py-0.5 rounded bg-gray-100 dark:bg-gray-800 text-gray-500 dark:text-gray-400">
              {{ TARGET_LABEL[v.target] }}
            </span>
            <span v-if="v.has_extra_input" class="text-xs px-1.5 py-0.5 rounded bg-blue-50 dark:bg-blue-950 text-blue-600 dark:text-blue-400">
              Saisie supplémentaire
            </span>
          </div>
          <p class="text-xs text-gray-400 dark:text-gray-500 line-clamp-2">{{ v.pre_prompt }}</p>
        </div>

        <!-- Actions -->
        <div class="flex items-center gap-2 shrink-0">
          <!-- Toggle actif -->
          <button
            :title="v.is_active ? 'Désactiver' : 'Activer'"
            :class="[
              'w-9 h-5 rounded-full transition-colors relative',
              v.is_active ? 'bg-brand-500' : 'bg-gray-300 dark:bg-gray-700'
            ]"
            @click="toggleActive(v)"
          >
            <span :class="[
              'absolute top-0.5 w-4 h-4 rounded-full bg-white shadow transition-transform',
              v.is_active ? 'translate-x-4' : 'translate-x-0.5'
            ]"/>
          </button>
          <button
            class="p-1.5 rounded text-gray-400 hover:text-brand-600 dark:hover:text-brand-400 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
            title="Modifier"
            @click="startEdit(v)"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
            </svg>
          </button>
          <button
            v-if="confirmDeleteId !== v.id"
            class="p-1.5 rounded text-gray-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-950 transition-colors"
            title="Supprimer"
            @click="confirmDeleteId = v.id"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
            </svg>
          </button>
          <div v-else class="flex items-center gap-1">
            <span class="text-xs text-red-500">Confirmer ?</span>
            <button class="text-xs text-red-500 font-medium hover:underline" @click="confirmDelete(v.id)">Oui</button>
            <button class="text-xs text-gray-400 hover:underline" @click="confirmDeleteId = null">Non</button>
          </div>
        </div>
      </div>
    </div>
    <p v-else class="text-sm text-gray-400">Aucune vérification configurée.</p>

    <!-- ── Formulaire ── -->
    <Transition name="fade">
      <div
        v-if="showForm"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/40"
        @click.self="cancelForm"
      >
        <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-2xl w-full max-w-2xl mx-4 max-h-[90vh] overflow-y-auto">
          <div class="p-6 space-y-4">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
              {{ editingId !== null ? 'Modifier la vérification' : 'Nouvelle vérification' }}
            </h2>

            <p v-if="error" class="text-sm text-red-500">{{ error }}</p>

            <!-- Label -->
            <div>
              <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                Label du bouton <span class="text-red-400">*</span>
              </label>
              <input
                v-model="form.label"
                type="text"
                placeholder="Ex : Style, Répétitions, Reformuler…"
                class="w-full text-sm rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-500"
              />
            </div>

            <!-- Cible -->
            <div>
              <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Cible</label>
              <div class="flex flex-wrap gap-3">
                <label
                  v-for="opt in ([
                    { value: 'all', label: 'Tout le texte' },
                    { value: 'selection', label: 'Sélection uniquement' },
                    { value: 'both', label: 'Sélection ou tout le texte' },
                  ] as const)"
                  :key="opt.value"
                  class="flex items-center gap-1.5 text-sm text-gray-700 dark:text-gray-300 cursor-pointer"
                >
                  <input type="radio" :value="opt.value" v-model="form.target" class="accent-brand-500" />
                  {{ opt.label }}
                </label>
              </div>
            </div>

            <!-- Saisie supplémentaire -->
            <div class="space-y-2">
              <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300 cursor-pointer">
                <input type="checkbox" v-model="form.has_extra_input" class="accent-brand-500" />
                Afficher une saisie supplémentaire (textarea)
              </label>
              <div v-if="form.has_extra_input" class="pl-6 space-y-2">
                <input
                  v-model="form.extra_input_label"
                  type="text"
                  placeholder="Label du champ (ex : Consigne)"
                  class="w-full text-sm rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-500"
                />
                <input
                  v-model="form.extra_input_placeholder"
                  type="text"
                  placeholder="Placeholder (ex : Rends cette phrase plus dynamique…)"
                  class="w-full text-sm rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-500"
                />
              </div>
            </div>

            <!-- Pré-prompt -->
            <div>
              <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                Pré-prompt (instructions LLM) <span class="text-red-400">*</span>
              </label>
              <textarea
                v-model="form.pre_prompt"
                rows="6"
                placeholder="Tu es un éditeur littéraire expert. Analyse le texte…"
                class="w-full text-sm rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-500 resize-y font-mono"
              />
              <p class="text-xs text-gray-400 mt-1">
                Le texte analysé et les instructions de format de réponse sont ajoutés automatiquement.
              </p>
            </div>

            <!-- Actif -->
            <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300 cursor-pointer">
              <input type="checkbox" v-model="form.is_active" class="accent-brand-500" />
              Actif (visible dans la barre d'outils)
            </label>

            <!-- Boutons -->
            <div class="flex justify-end gap-2 pt-2">
              <button
                class="px-4 py-2 text-sm rounded-lg text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
                @click="cancelForm"
              >
                Annuler
              </button>
              <button
                class="px-4 py-2 text-sm rounded-lg bg-brand-600 hover:bg-brand-700 text-white transition-colors disabled:opacity-50"
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
  </div>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.15s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
.line-clamp-2 { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
</style>
