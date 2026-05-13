<script setup lang="ts">
import { ref, watch, onMounted } from 'vue'
import { projectsService } from '@/services/projects.service'
import { activityService } from '@/services/activity.service'
import { useAuthStore } from '@/stores/auth.store'
import ActivityGrid from '@/components/activity/ActivityGrid.vue'
import ActivityHeatmap from '@/components/activity/ActivityHeatmap.vue'
import type { ActivityDay, ActivityHour, Project } from '@/types'

const props = defineProps<{ project: Project }>()
const emit  = defineEmits<{
  close:   []
  updated: [project: Project]
  deleted: [id: string]
}>()

const auth = useAuthStore()

// ── Navigation ────────────────────────────────────────────────
type TabKey = 'activity' | 'general' | 'goals' | 'export' | 'danger'

const TABS: { key: TabKey; label: string; icon: string }[] = [
  { key: 'activity', label: 'Activité',  icon: '📊' },
  { key: 'general',  label: 'Général',   icon: '📝' },
  { key: 'goals',    label: 'Objectifs', icon: '🎯' },
  { key: 'export',   label: 'Export',    icon: '📤' },
  { key: 'danger',   label: 'Danger',    icon: '⚠️' },
]

const activeTab     = ref<TabKey>('general')
const openAccordion = ref<TabKey | null>('general')

function toggleAccordion(key: TabKey) {
  openAccordion.value = openAccordion.value === key ? null : key
}

// ── Informations ──────────────────────────────────────────────
const title  = ref(props.project.title)
const genre  = ref(props.project.genre ?? '')
const status = ref<Project['status']>(props.project.status)
const saving = ref(false)
const saved  = ref(false)

watch(() => props.project, p => {
  title.value  = p.title
  genre.value  = p.genre ?? ''
  status.value = p.status
})

const STATUS_OPTS: { value: Project['status']; label: string; active: string; inactive: string }[] = [
  { value: 'draft',       label: 'Brouillon', active: 'bg-gray-500 text-white border-gray-500',   inactive: 'text-gray-500 border-gray-300 dark:border-gray-600 hover:border-gray-400' },
  { value: 'in_progress', label: 'En cours',  active: 'bg-blue-600 text-white border-blue-600',   inactive: 'text-blue-600 border-gray-300 dark:border-gray-600 hover:border-blue-400' },
  { value: 'revision',    label: 'Révision',  active: 'bg-amber-500 text-white border-amber-500', inactive: 'text-amber-600 border-gray-300 dark:border-gray-600 hover:border-amber-400' },
  { value: 'complete',    label: 'Terminé',   active: 'bg-green-600 text-white border-green-600', inactive: 'text-green-600 border-gray-300 dark:border-gray-600 hover:border-green-400' },
]

async function saveInfos() {
  saving.value = true; saved.value = false
  try {
    const updated = await projectsService.update(props.project.id, {
      title:  title.value.trim() || props.project.title,
      genre:  genre.value.trim() || null,
      status: status.value,
    })
    saved.value = true
    emit('updated', updated)
    setTimeout(() => { saved.value = false }, 2000)
  } finally { saving.value = false }
}

// ── Activité ──────────────────────────────────────────────────
const activityDays    = ref<ActivityDay[]>([])
const activityHours   = ref<ActivityHour[]>([])
const activityLoading = ref(true)

onMounted(async () => {
  try {
    const data = await activityService.forProject(props.project.id)
    activityDays.value  = data.daily
    activityHours.value = data.hourly
  } finally { activityLoading.value = false }
})

// ── Objectifs ─────────────────────────────────────────────────
const GOAL_LEVELS = [
  { key: 'project', label: 'Projet',   field: 'target_words'      as const },
  { key: 'arc',     label: 'Arc',      field: 'word_goal_arc'     as const },
  { key: 'chapter', label: 'Chapitre', field: 'word_goal_chapter' as const },
  { key: 'scene',   label: 'Scène',    field: 'word_goal_scene'   as const },
]

const goalInputs = ref<Record<string, string>>({
  project: String(props.project.target_words      || ''),
  arc:     String(props.project.word_goal_arc     ?? ''),
  chapter: String(props.project.word_goal_chapter ?? ''),
  scene:   String(props.project.word_goal_scene   ?? ''),
})
const goalSaving = ref(false)
const goalSaved  = ref(false)

watch(() => props.project, p => {
  goalInputs.value = {
    project: String(p.target_words      || ''),
    arc:     String(p.word_goal_arc     ?? ''),
    chapter: String(p.word_goal_chapter ?? ''),
    scene:   String(p.word_goal_scene   ?? ''),
  }
})

function inheritedGoal(key: string): number {
  const userPref = auth.user?.preferences?.wordGoals as Record<string, number | undefined> | undefined
  const appDef   = auth.user?.word_goal_defaults     as Record<string, number>             | undefined
  return userPref?.[key] ?? appDef?.[key] ?? 0
}
function goalPlaceholder(key: string) {
  const v = inheritedGoal(key)
  return v ? v.toLocaleString('fr-FR') : '—'
}

async function saveGoals() {
  goalSaving.value = true; goalSaved.value = false
  try {
    const payload: Record<string, number | null> = {}
    for (const { key, field } of GOAL_LEVELS) {
      const v = parseInt(goalInputs.value[key] ?? '', 10)
      payload[field] = isNaN(v) || v <= 0 ? null : v
    }
    const updated = await projectsService.update(props.project.id, payload as Partial<Project>)
    goalSaved.value = true
    emit('updated', updated)
    setTimeout(() => { goalSaved.value = false }, 2000)
  } finally { goalSaving.value = false }
}

// ── Export ────────────────────────────────────────────────────
const exporting = ref<string | null>(null)

async function exportProject(format: 'txt' | 'md' | 'zip') {
  exporting.value = format
  try {
    const blob = await projectsService.export(props.project.id, format)
    const name = title.value.trim().replace(/\s+/g, '-').toLowerCase() + '.' + format
    const url  = URL.createObjectURL(blob)
    const a    = document.createElement('a')
    a.href = url; a.download = name; a.click()
    URL.revokeObjectURL(url)
  } finally { exporting.value = null }
}

// ── Suppression ───────────────────────────────────────────────
const deleteStep    = ref<'idle' | 'confirm'>('idle')
const deleteConfirm = ref('')
const deleting      = ref(false)

async function confirmDelete() {
  if (deleteConfirm.value.trim().toLowerCase() !== props.project.title.trim().toLowerCase()) return
  deleting.value = true
  try {
    await projectsService.destroy(props.project.id)
    emit('deleted', props.project.id)
    emit('close')
  } finally { deleting.value = false }
}
</script>

<template>
  <Teleport to="body">
    <div
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm p-2 md:p-4"
      @click.self="emit('close')"
    >
      <div class="w-full max-w-sm md:max-w-4xl bg-white dark:bg-gray-900 rounded-2xl shadow-2xl flex flex-col max-h-[92dvh] md:h-[640px] overflow-hidden">

        <!-- ── En-tête ── -->
        <div class="shrink-0 flex items-center justify-between px-5 md:px-6 py-4 border-b border-gray-200 dark:border-gray-800">
          <div class="min-w-0">
            <h2 class="font-semibold text-gray-900 dark:text-gray-100 truncate leading-tight">{{ project.title }}</h2>
            <p class="text-xs text-gray-400 mt-0.5">
              {{ project.content_type?.short_name ?? project.content_type?.name ?? 'Projet' }}
            </p>
          </div>
          <button
            class="ml-4 shrink-0 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors"
            @click="emit('close')"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
          </button>
        </div>

        <!-- ── MOBILE : Accordéons ── -->
        <div class="md:hidden flex-1 overflow-y-auto divide-y divide-gray-100 dark:divide-gray-800">
          <div v-for="tab in TABS" :key="tab.key">

            <!-- Header accordéon -->
            <button
              class="w-full flex items-center justify-between px-5 py-3.5 text-left transition-colors hover:bg-gray-50 dark:hover:bg-gray-800/50"
              :class="openAccordion === tab.key ? 'bg-gray-50 dark:bg-gray-800/40' : ''"
              @click="toggleAccordion(tab.key)"
            >
              <span class="flex items-center gap-2.5">
                <span class="text-base leading-none">{{ tab.icon }}</span>
                <span
                  class="text-sm font-medium"
                  :class="openAccordion === tab.key
                    ? 'text-brand-600 dark:text-brand-400'
                    : 'text-gray-700 dark:text-gray-300'"
                >{{ tab.label }}</span>
              </span>
              <svg
                class="w-4 h-4 text-gray-400 transition-transform duration-200"
                :class="openAccordion === tab.key ? 'rotate-180' : ''"
                fill="none" stroke="currentColor" viewBox="0 0 24 24"
              >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
              </svg>
            </button>

            <!-- Contenu accordéon -->
            <Transition name="accordion">
              <div v-if="openAccordion === tab.key" class="px-5 pb-5 pt-1">

                <!-- Activité -->
                <template v-if="tab.key === 'activity'">
                  <div v-if="activityLoading" class="text-xs text-gray-400 py-4">Chargement…</div>
                  <template v-else>
                    <p class="text-xs text-gray-400 mb-2 mt-2">365 derniers jours</p>
                    <ActivityGrid :days="activityDays" />
                    <p class="text-xs text-gray-400 mb-2 mt-4">Habitudes par heure</p>
                    <ActivityHeatmap :hours="activityHours" />
                  </template>
                </template>

                <!-- Général -->
                <template v-else-if="tab.key === 'general'">
                  <div class="space-y-3 pt-2">
                    <div>
                      <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Titre</label>
                      <input v-model="title" type="text" class="input-field" />
                    </div>
                    <div>
                      <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Genre</label>
                      <input v-model="genre" type="text" placeholder="Fantasy, Thriller…" class="input-field" />
                    </div>
                    <div>
                      <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Statut</label>
                      <div class="flex gap-2 flex-wrap">
                        <button
                          v-for="opt in STATUS_OPTS" :key="opt.value"
                          class="text-xs px-3 py-1.5 rounded-lg border transition-colors font-medium"
                          :class="status === opt.value ? opt.active : opt.inactive"
                          @click="status = opt.value"
                        >{{ opt.label }}</button>
                      </div>
                    </div>
                    <div class="flex items-center gap-3 pt-1">
                      <button :disabled="saving" class="btn-primary" @click="saveInfos">
                        {{ saving ? 'Enregistrement…' : 'Enregistrer' }}
                      </button>
                      <Transition name="fade">
                        <span v-if="saved" class="text-xs text-green-600 dark:text-green-400">Enregistré ✓</span>
                      </Transition>
                    </div>
                  </div>
                </template>

                <!-- Objectifs -->
                <template v-else-if="tab.key === 'goals'">
                  <p class="text-xs text-gray-400 mb-4 mt-2">
                    Laissez vide pour hériter de vos paramètres utilisateur ou des valeurs par défaut.
                  </p>
                  <div class="space-y-3">
                    <div v-for="level in GOAL_LEVELS" :key="level.key" class="flex items-center gap-3">
                      <span class="w-20 shrink-0 text-xs font-medium text-gray-500 dark:text-gray-400">{{ level.label }}</span>
                      <div class="flex-1 relative">
                        <input
                          v-model="goalInputs[level.key]"
                          type="number" min="1"
                          :placeholder="goalPlaceholder(level.key)"
                          class="input-field pr-14"
                        />
                        <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-gray-400 pointer-events-none">mots</span>
                      </div>
                    </div>
                  </div>
                  <div class="flex items-center gap-3 mt-4">
                    <button :disabled="goalSaving" class="btn-primary" @click="saveGoals">
                      {{ goalSaving ? 'Enregistrement…' : 'Enregistrer' }}
                    </button>
                    <Transition name="fade">
                      <span v-if="goalSaved" class="text-xs text-green-600 dark:text-green-400">Enregistré ✓</span>
                    </Transition>
                  </div>
                </template>

                <!-- Export -->
                <template v-else-if="tab.key === 'export'">
                  <p class="text-xs text-gray-400 mb-3 mt-2">Assemblé dans l'ordre arcs → chapitres → scènes.</p>
                  <div class="grid grid-cols-2 gap-2">
                    <button :disabled="exporting !== null" class="export-btn" @click="exportProject('txt')">
                      <span class="text-xl">📄</span>
                      <span><span class="font-medium block text-sm">Texte brut</span><span class="text-xs text-gray-400">.txt</span></span>
                      <span v-if="exporting === 'txt'" class="ml-auto text-xs text-gray-400">…</span>
                    </button>
                    <button :disabled="exporting !== null" class="export-btn" @click="exportProject('md')">
                      <span class="text-xl">✍️</span>
                      <span><span class="font-medium block text-sm">Markdown</span><span class="text-xs text-gray-400">.md</span></span>
                      <span v-if="exporting === 'md'" class="ml-auto text-xs text-gray-400">…</span>
                    </button>
                    <button disabled class="export-btn opacity-40 cursor-not-allowed" title="Bientôt disponible">
                      <span class="text-xl">📕</span>
                      <span><span class="font-medium block text-sm">PDF</span><span class="text-xs text-gray-400">Bientôt</span></span>
                    </button>
                    <button :disabled="exporting !== null" class="export-btn" @click="exportProject('zip')">
                      <span class="text-xl">🗜️</span>
                      <span><span class="font-medium block text-sm">ZIP</span><span class="text-xs text-gray-400">1 fichier/scène</span></span>
                      <span v-if="exporting === 'zip'" class="ml-auto text-xs text-gray-400">…</span>
                    </button>
                  </div>
                </template>

                <!-- Danger -->
                <template v-else-if="tab.key === 'danger'">
                  <div class="pt-2">
                    <div v-if="deleteStep === 'idle'" class="flex items-center justify-between rounded-xl border border-red-200 dark:border-red-900 bg-red-50 dark:bg-red-900/10 px-4 py-3">
                      <div>
                        <p class="text-sm font-medium text-red-700 dark:text-red-400">Supprimer ce projet</p>
                        <p class="text-xs text-red-500 mt-0.5">Scènes, annotations et fiches perdues définitivement.</p>
                      </div>
                      <button class="ml-4 shrink-0 btn-danger" @click="deleteStep = 'confirm'; deleteConfirm = ''">Supprimer</button>
                    </div>
                    <div v-else class="rounded-xl border border-red-300 dark:border-red-800 bg-red-50 dark:bg-red-900/10 px-4 py-4 space-y-3">
                      <p class="text-sm text-red-700 dark:text-red-400 font-medium">⚠ Action irréversible. Tous les contenus associés seront supprimés.</p>
                      <p class="text-xs text-red-600 dark:text-red-400">Tape <strong>{{ project.title }}</strong> pour confirmer :</p>
                      <input v-model="deleteConfirm" type="text" :placeholder="project.title" class="input-field border-red-300 dark:border-red-700 focus:ring-red-500" />
                      <div class="flex gap-2">
                        <button
                          :disabled="deleteConfirm.trim().toLowerCase() !== project.title.trim().toLowerCase() || deleting"
                          class="btn-danger disabled:opacity-40"
                          @click="confirmDelete"
                        >{{ deleting ? 'Suppression…' : 'Confirmer' }}</button>
                        <button class="text-sm text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 px-3" @click="deleteStep = 'idle'">Annuler</button>
                      </div>
                    </div>
                  </div>
                </template>

              </div>
            </Transition>
          </div>
        </div>

        <!-- ── DESKTOP : Tabs verticaux ── -->
        <div class="hidden md:flex flex-1 min-h-0">

          <!-- Sidebar -->
          <nav class="w-44 shrink-0 border-r border-gray-200 dark:border-gray-800 p-3 space-y-0.5 overflow-y-auto">
            <button
              v-for="tab in TABS"
              :key="tab.key"
              class="w-full flex items-center gap-2.5 px-3 py-2.5 rounded-lg text-sm transition-colors text-left"
              :class="activeTab === tab.key
                ? 'bg-white dark:bg-gray-800 text-brand-600 dark:text-brand-400 font-medium shadow-sm'
                : 'text-gray-600 dark:text-gray-400 hover:bg-white/60 dark:hover:bg-gray-800/50'"
              @click="activeTab = tab.key"
            >
              <span class="text-base leading-none">{{ tab.icon }}</span>
              {{ tab.label }}
            </button>
          </nav>

          <!-- Contenu -->
          <div class="flex-1 overflow-y-auto px-6 py-5">

            <!-- Activité -->
            <section v-if="activeTab === 'activity'">
              <h3 class="section-title mb-4">Activité sur ce projet</h3>
              <div v-if="activityLoading" class="text-xs text-gray-400">Chargement…</div>
              <template v-else>
                <p class="text-xs text-gray-400 mb-2">365 derniers jours</p>
                <ActivityGrid :days="activityDays" />
                <p class="text-xs text-gray-400 mb-2 mt-6">Habitudes par heure</p>
                <ActivityHeatmap :hours="activityHours" />
              </template>
            </section>

            <!-- Général -->
            <section v-else-if="activeTab === 'general'">
              <h3 class="section-title mb-4">Informations générales</h3>
              <div class="space-y-4 max-w-sm">
                <div>
                  <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Titre</label>
                  <input v-model="title" type="text" class="input-field" />
                </div>
                <div>
                  <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Genre</label>
                  <input v-model="genre" type="text" placeholder="Fantasy, Thriller, Romance…" class="input-field" />
                </div>
                <div>
                  <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Statut</label>
                  <div class="flex gap-2 flex-wrap">
                    <button
                      v-for="opt in STATUS_OPTS" :key="opt.value"
                      class="text-xs px-3 py-1.5 rounded-lg border transition-colors font-medium"
                      :class="status === opt.value ? opt.active : opt.inactive"
                      @click="status = opt.value"
                    >{{ opt.label }}</button>
                  </div>
                </div>
                <div class="flex items-center gap-3 pt-1">
                  <button :disabled="saving" class="btn-primary" @click="saveInfos">
                    {{ saving ? 'Enregistrement…' : 'Enregistrer' }}
                  </button>
                  <Transition name="fade">
                    <span v-if="saved" class="text-xs text-green-600 dark:text-green-400">Enregistré ✓</span>
                  </Transition>
                </div>
              </div>
            </section>

            <!-- Objectifs -->
            <section v-else-if="activeTab === 'goals'">
              <h3 class="section-title mb-1">Objectifs de mots</h3>
              <p class="text-sm text-gray-500 dark:text-gray-400 mb-5">
                Laissez vide pour hériter de vos paramètres utilisateur ou des valeurs par défaut de l'application.
              </p>
              <div class="space-y-3 max-w-sm">
                <div v-for="level in GOAL_LEVELS" :key="level.key" class="flex items-center gap-3">
                  <span class="w-24 shrink-0 text-sm font-medium text-gray-600 dark:text-gray-400">{{ level.label }}</span>
                  <div class="flex-1 relative">
                    <input
                      v-model="goalInputs[level.key]"
                      type="number" min="1"
                      :placeholder="goalPlaceholder(level.key)"
                      class="input-field pr-14"
                    />
                    <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-gray-400 pointer-events-none">mots</span>
                  </div>
                </div>
              </div>
              <div class="flex items-center gap-3 mt-5">
                <button :disabled="goalSaving" class="btn-primary" @click="saveGoals">
                  {{ goalSaving ? 'Enregistrement…' : 'Enregistrer' }}
                </button>
                <Transition name="fade">
                  <span v-if="goalSaved" class="text-xs text-green-600 dark:text-green-400">Enregistré ✓</span>
                </Transition>
              </div>
            </section>

            <!-- Export -->
            <section v-else-if="activeTab === 'export'">
              <h3 class="section-title mb-1">Exporter le projet</h3>
              <p class="text-sm text-gray-500 dark:text-gray-400 mb-5">Assemblé dans l'ordre arcs → chapitres → scènes.</p>
              <div class="grid grid-cols-2 gap-3 max-w-sm">
                <button :disabled="exporting !== null" class="export-btn" @click="exportProject('txt')">
                  <span class="text-2xl">📄</span>
                  <span><span class="font-medium block">Texte brut</span><span class="text-xs text-gray-400">.txt</span></span>
                  <span v-if="exporting === 'txt'" class="ml-auto text-xs text-gray-400">…</span>
                </button>
                <button :disabled="exporting !== null" class="export-btn" @click="exportProject('md')">
                  <span class="text-2xl">✍️</span>
                  <span><span class="font-medium block">Markdown</span><span class="text-xs text-gray-400">.md</span></span>
                  <span v-if="exporting === 'md'" class="ml-auto text-xs text-gray-400">…</span>
                </button>
                <button disabled class="export-btn opacity-40 cursor-not-allowed" title="Bientôt disponible">
                  <span class="text-2xl">📕</span>
                  <span><span class="font-medium block">PDF</span><span class="text-xs text-gray-400">Bientôt disponible</span></span>
                </button>
                <button :disabled="exporting !== null" class="export-btn" @click="exportProject('zip')">
                  <span class="text-2xl">🗜️</span>
                  <span><span class="font-medium block">ZIP</span><span class="text-xs text-gray-400">1 fichier / scène</span></span>
                  <span v-if="exporting === 'zip'" class="ml-auto text-xs text-gray-400">…</span>
                </button>
              </div>
            </section>

            <!-- Danger -->
            <section v-else-if="activeTab === 'danger'">
              <h3 class="text-xs font-semibold text-red-400 uppercase tracking-wider mb-4">Zone de danger</h3>
              <div v-if="deleteStep === 'idle'" class="flex items-center justify-between rounded-xl border border-red-200 dark:border-red-900 bg-red-50 dark:bg-red-900/10 px-4 py-3 max-w-md">
                <div>
                  <p class="text-sm font-medium text-red-700 dark:text-red-400">Supprimer ce projet</p>
                  <p class="text-xs text-red-500 mt-0.5">Toutes les scènes, annotations et fiches seront perdues définitivement.</p>
                </div>
                <button class="ml-4 shrink-0 btn-danger" @click="deleteStep = 'confirm'; deleteConfirm = ''">Supprimer</button>
              </div>
              <div v-else class="rounded-xl border border-red-300 dark:border-red-800 bg-red-50 dark:bg-red-900/10 px-4 py-4 space-y-3 max-w-md">
                <p class="text-sm text-red-700 dark:text-red-400 font-medium">⚠ Cette action est irréversible. Tous les arcs, chapitres, scènes, annotations, notes et fiches associés seront supprimés.</p>
                <p class="text-xs text-red-600 dark:text-red-400">Tape <strong>{{ project.title }}</strong> pour confirmer :</p>
                <input v-model="deleteConfirm" type="text" :placeholder="project.title" class="input-field border-red-300 dark:border-red-700 focus:ring-red-500" />
                <div class="flex gap-2">
                  <button
                    :disabled="deleteConfirm.trim().toLowerCase() !== project.title.trim().toLowerCase() || deleting"
                    class="btn-danger disabled:opacity-40"
                    @click="confirmDelete"
                  >{{ deleting ? 'Suppression…' : 'Confirmer la suppression' }}</button>
                  <button class="px-4 py-2 text-sm text-gray-500 hover:text-gray-700 dark:hover:text-gray-300" @click="deleteStep = 'idle'">Annuler</button>
                </div>
              </div>
            </section>

          </div>
        </div>

      </div>
    </div>
  </Teleport>
</template>

<style scoped>
@reference "@/assets/main.css";

.section-title {
  @apply text-base font-semibold text-gray-900 dark:text-gray-100;
}
.input-field {
  @apply w-full text-sm rounded-lg border border-gray-300 dark:border-gray-600
         bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100
         px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-500;
}
.btn-primary {
  @apply px-4 py-2 text-sm font-medium rounded-lg bg-brand-600 text-white
         hover:bg-brand-700 disabled:opacity-50 transition-colors;
}
.btn-danger {
  @apply px-3 py-1.5 text-xs font-medium rounded-lg bg-red-600 text-white hover:bg-red-700 transition-colors;
}
.export-btn {
  @apply flex items-center gap-2 px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-700
         hover:border-brand-400 hover:bg-brand-50 dark:hover:bg-brand-900/20
         transition-colors text-gray-700 dark:text-gray-300 disabled:opacity-50;
}

/* Accordéon */
.accordion-enter-active,
.accordion-leave-active {
  transition: opacity 0.15s ease, max-height 0.2s ease;
  max-height: 600px;
  overflow: hidden;
}
.accordion-enter-from,
.accordion-leave-to {
  opacity: 0;
  max-height: 0;
}

/* Fade */
.fade-enter-active, .fade-leave-active { transition: opacity 0.3s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
