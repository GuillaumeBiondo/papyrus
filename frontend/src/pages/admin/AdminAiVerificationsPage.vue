<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { adminService } from '@/services/admin.service'
import type { AiStats, AiVerification } from '@/types'

const CARD_TYPES = [
  { key: 'personnage', label: 'Personnages' },
  { key: 'lieu',       label: 'Lieux' },
  { key: 'evenement',  label: 'Événements' },
  { key: 'objet',      label: 'Objets' },
  { key: 'theme',      label: 'Thèmes' },
]

// ── Tab ───────────────────────────────────────────────────────
const activeTab = ref<'config' | 'stats'>('config')

// ── Révisions (config) ────────────────────────────────────────
const verifications = ref<AiVerification[]>([])
const loadingConfig = ref(true)

const showForm  = ref(false)
const editingId = ref<number | null>(null)
const saving    = ref(false)
const error     = ref('')

const form = ref<Partial<AiVerification>>({
  label: '', description: '', is_active: true, target: 'all',
  has_extra_input: false, extra_input_label: '',
  extra_input_placeholder: '', pre_prompt: '',
  allowed_card_types: null, allow_multiple_cards: false,
})

function resetForm() {
  editingId.value = null
  form.value = {
    label: '', description: '', is_active: true, target: 'all',
    has_extra_input: false, extra_input_label: '',
    extra_input_placeholder: '', pre_prompt: '',
    allowed_card_types: null, allow_multiple_cards: false,
  }
}

function startCreate() { resetForm(); showForm.value = true }
function startEdit(v: AiVerification) { editingId.value = v.id; form.value = { ...v }; showForm.value = true }
function cancelForm() { showForm.value = false; resetForm() }

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

async function toggleActive(v: AiVerification) {
  v.is_active = !v.is_active
  await adminService.updateAiVerification(v.id, { is_active: v.is_active })
}

const confirmDeleteId = ref<number | null>(null)
async function confirmDelete(id: number) {
  await adminService.deleteAiVerification(id)
  verifications.value = verifications.value.filter(v => v.id !== id)
  confirmDeleteId.value = null
}

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

// ── Stats ─────────────────────────────────────────────────────
const stats       = ref<AiStats | null>(null)
const loadingStats = ref(false)
const statsLoaded  = ref(false)

async function loadStats() {
  if (statsLoaded.value) return
  loadingStats.value = true
  try {
    stats.value = await adminService.getAiStats()
    statsLoaded.value = true
  } finally {
    loadingStats.value = false
  }
}

async function switchTab(tab: 'config' | 'stats') {
  activeTab.value = tab
  if (tab === 'stats') await loadStats()
}

// Cost estimate: 4 chars ≈ 1 token, output ≈ 25% of input (conservative)
// gpt-4o-mini: $0.15/1M input + $0.60/1M output
// Use est_tokens from backend (input chars / 4) and add output estimate
function formatCost(usd: number) {
  if (usd < 0.01) return `< $0.01`
  return `$${usd.toFixed(3)}`
}

function formatDate(iso: string | null) {
  if (!iso) return '—'
  return new Date(iso).toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: '2-digit' })
}

function formatNumber(n: number) {
  return new Intl.NumberFormat('fr-FR').format(n)
}

// Sparkline: map daily calls to a simple SVG path
const sparkline = computed(() => {
  if (!stats.value?.daily.length) return ''
  const vals = stats.value.daily.map(d => d.calls)
  const max = Math.max(...vals, 1)
  const w = 120, h = 28
  const points = vals.map((v, i) => {
    const x = (i / Math.max(vals.length - 1, 1)) * w
    const y = h - (v / max) * h
    return `${x},${y}`
  })
  return `M ${points.join(' L ')}`
})

// ── Types de fiches ───────────────────────────────────────────
function toggleCardType(key: string) {
  const current = form.value.allowed_card_types ?? []
  const idx = current.indexOf(key)
  if (idx === -1) form.value.allowed_card_types = [...current, key]
  else form.value.allowed_card_types = current.filter(s => s !== key)
}

// ── Chargement initial ────────────────────────────────────────
onMounted(async () => {
  try {
    const { verifications: list } = await adminService.getAiVerifications()
    verifications.value = list
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
        <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-100">Révisions IA</h1>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
          Boutons de révision de l'éditeur — configuration et statistiques d'usage.
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
          class="flex items-center gap-2 px-3 py-1.5 rounded-lg text-sm bg-brand-600 hover:bg-brand-700 text-white transition-colors"
          @click="startCreate"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
          </svg>
          Ajouter
        </button>
      </div>

      <div v-if="loadingConfig" class="text-sm text-gray-400">Chargement…</div>

      <div v-else-if="verifications.length" class="space-y-2">
        <div
          v-for="(v, idx) in verifications"
          :key="v.id"
          class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-4 flex items-start gap-4"
        >
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

          <div class="flex-1 min-w-0 space-y-1.5">
            <div class="flex items-center gap-2 flex-wrap">
              <span class="font-medium text-gray-900 dark:text-gray-100">{{ v.label }}</span>
              <span class="text-xs px-1.5 py-0.5 rounded bg-gray-100 dark:bg-gray-800 text-gray-500 dark:text-gray-400">
                {{ TARGET_LABEL[v.target] }}
              </span>
              <span v-if="v.has_extra_input" class="text-xs px-1.5 py-0.5 rounded bg-blue-50 dark:bg-blue-950 text-blue-600 dark:text-blue-400">
                Saisie supplémentaire
              </span>
              <span v-if="v.allowed_card_types?.length" class="text-xs px-1.5 py-0.5 rounded bg-purple-50 dark:bg-purple-950 text-purple-600 dark:text-purple-400">
                Fiches : {{ v.allowed_card_types.join(', ') }}{{ v.allow_multiple_cards ? ' (multi)' : '' }}
              </span>
            </div>
            <p v-if="v.description" class="text-xs text-gray-500 dark:text-gray-400 italic line-clamp-1">{{ v.description }}</p>
            <p class="text-xs text-gray-400 dark:text-gray-500 line-clamp-2">{{ v.pre_prompt }}</p>
          </div>

          <div class="flex items-center gap-2 shrink-0">
            <button
              :title="v.is_active ? 'Désactiver' : 'Activer'"
              :class="['w-9 h-5 rounded-full transition-colors relative', v.is_active ? 'bg-brand-500' : 'bg-gray-300 dark:bg-gray-700']"
              @click="toggleActive(v)"
            >
              <span :class="['absolute left-0 top-0.5 w-4 h-4 rounded-full bg-white shadow transition-transform', v.is_active ? 'translate-x-4' : 'translate-x-0.5']"/>
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
      <p v-else class="text-sm text-gray-400">Aucune révision configurée.</p>
    </template>

    <!-- ══ TAB STATS ══ -->
    <template v-else>
      <div v-if="loadingStats" class="text-sm text-gray-400 py-8 text-center">Chargement des statistiques…</div>

      <template v-else-if="stats">
        <!-- Totaux -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
          <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-4">
            <p class="text-xs text-gray-500 dark:text-gray-400">Appels totaux</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-gray-100 mt-1">{{ formatNumber(stats.totals.calls) }}</p>
          </div>
          <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-4">
            <p class="text-xs text-gray-500 dark:text-gray-400">Tokens estimés</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-gray-100 mt-1">{{ formatNumber(stats.totals.est_tokens) }}</p>
            <p class="text-xs text-gray-400 mt-0.5">≈ chars / 4</p>
          </div>
          <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-4">
            <p class="text-xs text-gray-500 dark:text-gray-400">Coût estimé (input)</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-gray-100 mt-1">{{ formatCost(stats.totals.estimated_cost) }}</p>
            <p class="text-xs text-gray-400 mt-0.5">hors tokens output</p>
          </div>
          <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-4">
            <p class="text-xs text-gray-500 dark:text-gray-400">Coût / appel moy.</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-gray-100 mt-1">
              {{ stats.totals.calls ? formatCost(stats.totals.estimated_cost / stats.totals.calls) : '—' }}
            </p>
          </div>
        </div>

        <!-- Sparkline 30j -->
        <div v-if="stats.daily.length" class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-4">
          <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-3">Appels — 30 derniers jours</p>
          <svg viewBox="0 -4 120 36" class="w-full h-10" preserveAspectRatio="none">
            <path :d="sparkline" fill="none" stroke="currentColor" stroke-width="1.5" class="text-brand-500" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
          <div class="flex justify-between text-xs text-gray-400 mt-1">
            <span>{{ formatDate(stats.daily[0]?.date ?? null) }}</span>
            <span>{{ formatDate(stats.daily[stats.daily.length - 1]?.date ?? null) }}</span>
          </div>
        </div>

        <!-- Par révision -->
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl overflow-hidden">
          <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-800">
            <p class="text-sm font-medium text-gray-900 dark:text-gray-100">Par bouton de révision</p>
          </div>
          <div v-if="stats.by_revision.length" class="divide-y divide-gray-100 dark:divide-gray-800">
            <div
              v-for="r in stats.by_revision"
              :key="r.label"
              class="px-4 py-3 flex items-center gap-4"
            >
              <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ r.label }}</p>
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
                  <p class="text-xs text-gray-400">sugg. moy.</p>
                </div>
                <div>
                  <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ formatNumber(r.est_tokens) }}</p>
                  <p class="text-xs text-gray-400">tokens est.</p>
                </div>
              </div>
            </div>
          </div>
          <p v-else class="px-4 py-6 text-sm text-gray-400 text-center">Aucune donnée.</p>
        </div>

        <!-- Par utilisateur -->
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl overflow-hidden">
          <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-800">
            <p class="text-sm font-medium text-gray-900 dark:text-gray-100">Par utilisateur <span class="text-xs text-gray-400 font-normal">(top 50)</span></p>
          </div>
          <div v-if="stats.by_user.length" class="divide-y divide-gray-100 dark:divide-gray-800">
            <div
              v-for="(u, i) in stats.by_user"
              :key="u.user_id"
              class="px-4 py-3 flex items-center gap-4"
            >
              <span class="text-xs text-gray-400 w-5 text-right shrink-0">{{ i + 1 }}</span>
              <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">{{ u.name }}</p>
                <p class="text-xs text-gray-400 truncate">{{ u.email }}</p>
              </div>
              <div class="flex items-center gap-6 text-right shrink-0">
                <div>
                  <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ formatNumber(u.calls) }}</p>
                  <p class="text-xs text-gray-400">appels</p>
                </div>
                <div>
                  <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ formatNumber(u.est_tokens) }}</p>
                  <p class="text-xs text-gray-400">tokens est.</p>
                </div>
                <div>
                  <p class="text-xs text-gray-400">{{ formatDate(u.last_used_at) }}</p>
                </div>
              </div>
            </div>
          </div>
          <p v-else class="px-4 py-6 text-sm text-gray-400 text-center">Aucune donnée.</p>
        </div>

        <p class="text-xs text-gray-400">
          Estimation du coût basée sur les tokens d'entrée uniquement (chars / 4).
          Les tokens de sortie représentent généralement 20–40% supplémentaires selon la quantité de suggestions.
          Tarif gpt-4o-mini : $0,15 / 1M tokens input.
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
            {{ editingId !== null ? 'Modifier la révision' : 'Nouvelle révision' }}
          </h2>

          <p v-if="error" class="text-sm text-red-500">{{ error }}</p>

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

          <div>
            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
              Description (infobulle au survol)
            </label>
            <input
              v-model="form.description"
              type="text"
              placeholder="Ex : Détecte les répétitions de mots dans un périmètre proche…"
              class="w-full text-sm rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-500"
            />
          </div>

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

          <!-- Sélection de types de fiches -->
          <div class="space-y-2">
            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">
              Proposer une sélection de fiches
            </label>
            <div class="flex flex-wrap gap-2">
              <label
                v-for="ct in CARD_TYPES"
                :key="ct.key"
                class="flex items-center gap-1.5 text-sm cursor-pointer px-2.5 py-1 rounded-lg border transition-colors"
                :class="(form.allowed_card_types ?? []).includes(ct.key)
                  ? 'border-brand-400 bg-brand-50 dark:bg-brand-950 text-brand-700 dark:text-brand-300'
                  : 'border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:border-gray-300 dark:hover:border-gray-600'"
              >
                <input
                  type="checkbox"
                  class="accent-brand-500"
                  :checked="(form.allowed_card_types ?? []).includes(ct.key)"
                  @change="toggleCardType(ct.key)"
                />
                {{ ct.label }}
              </label>
            </div>
            <label
              v-if="(form.allowed_card_types ?? []).length > 0"
              class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300 cursor-pointer"
            >
              <input type="checkbox" v-model="form.allow_multiple_cards" class="accent-brand-500" />
              Autoriser la sélection de plusieurs fiches
            </label>
          </div>

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

          <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300 cursor-pointer">
            <input type="checkbox" v-model="form.is_active" class="accent-brand-500" />
            Actif (visible dans la barre d'outils)
          </label>

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
</template>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.15s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
.line-clamp-2 { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
</style>
