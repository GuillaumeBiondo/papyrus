<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useProjectsStore } from '@/stores/projects.store'
import { useAuthStore } from '@/stores/auth.store'
import { useThemeStore } from '@/stores/theme.store'
import { useAppConfigStore } from '@/stores/appConfig.store'
import { ACCENT_PALETTES } from '@/utils/accentColors'
import ProjectSettingsDialog from '@/components/dashboard/ProjectSettingsDialog.vue'
import GenreSelector from '@/components/ui/GenreSelector.vue'
import { getGenreName, getCategoryForGenre } from '@/data/genres'
import { projectsService } from '@/services/projects.service'
import type { Project } from '@/types'

const router = useRouter()
const projects = useProjectsStore()
const auth = useAuthStore()
const theme = useThemeStore()
const appConfig = useAppConfigStore()

// ── Premium ───────────────────────────────────────────────
const isPremium = computed(() => auth.user?.effective_premium ?? false)
const projectLimit = computed(() => appConfig.config?.premium_project_limit ?? 1)
const atProjectLimit = computed(() =>
  !isPremium.value && projects.projects.length >= projectLimit.value
)

// ── Types de contenu ──────────────────────────────────────
type ContentTypeItem = { id: string; name: string; short_name: string | null; slug: string; is_premium: boolean; description: string | null }
const contentTypes = ref<ContentTypeItem[]>([])

// ── Création (étape 1 : choix du type) ───────────────────
const showTypeSelect = ref(false)
const selectedContentType = ref<ContentTypeItem | null>(null)

// ── Création (étape 2 : formulaire) ──────────────────────
const showNewForm = ref(false)
const newTitle = ref('')
const newGenres = ref<string[]>([])
const creating = ref(false)

onMounted(async () => {
  projects.fetchAll()
  appConfig.fetch()
  try {
    const res = await projectsService.getActiveContentTypes()
    contentTypes.value = res.content_types
  } catch {
    // silently ignore — fallback to no type
  }
})

function openTypeSelect() {
  if (contentTypes.value.length <= 1) {
    selectedContentType.value = contentTypes.value[0] ?? null
    showNewForm.value = true
  } else {
    showTypeSelect.value = true
  }
}

function selectType(ct: ContentTypeItem) {
  if (ct.is_premium && !isPremium.value) return
  selectedContentType.value = ct
  showTypeSelect.value = false
  showNewForm.value = true
}

function cancelTypeSelect() {
  showTypeSelect.value = false
}

function cancelNewForm() {
  showNewForm.value = false
  newTitle.value = ''
  newGenres.value = []
  selectedContentType.value = null
}

async function createProject() {
  if (!newTitle.value.trim()) return
  creating.value = true
  try {
    const project = await projects.create({
      title: newTitle.value.trim(),
      genres: newGenres.value.length > 0 ? newGenres.value : undefined,
      content_type_id: selectedContentType.value?.id,
    } as any)
    cancelNewForm()
    router.push({ name: 'editor', params: { projectId: project.id } })
  } finally {
    creating.value = false
  }
}

// ── Tri & recherche ───────────────────────────────────────
const search = ref('')
const sort = ref<'recent' | 'title' | 'progress'>('recent')

const filtered = computed(() => {
  let list = [...projects.projects]
  if (search.value.trim()) {
    const q = search.value.toLowerCase()
    list = list.filter(p => p.title.toLowerCase().includes(q) || p.genres?.some(g => g.toLowerCase().includes(q)))
  }
  if (sort.value === 'title') list.sort((a, b) => a.title.localeCompare(b.title))
  else if (sort.value === 'progress') list.sort((a, b) => wordPct(b) - wordPct(a))
  return list
})

// ── Helpers visuels ───────────────────────────────────────
const STATUS_LABEL: Record<Project['status'], string> = {
  draft: 'brouillon', in_progress: 'en cours', revision: 'révision', complete: 'terminé',
}
const STATUS_COLOR: Record<Project['status'], { bg: string; text: string }> = {
  draft:       { bg: '#9ca3af', text: '#ffffff' },
  in_progress: { bg: '#3b82f6', text: '#ffffff' },
  revision:    { bg: '#f59e0b', text: '#ffffff' },
  complete:    { bg: '#22c55e', text: '#ffffff' },
}

function wordPct(p: Project) {
  return p.target_words ? Math.round((p.word_count / p.target_words) * 100) : 0
}
function scenePct(p: Project) {
  return p.target_scenes ? Math.round((p.scene_count / p.target_scenes) * 100) : 0
}

const brandFallbackColor = computed(() => {
  const mode = theme.applied
  const accent = (auth.preferences as any)?.[mode]?.accentColor ?? 'indigo'
  const palette = ACCENT_PALETTES.find(p => p.key === accent)
  if (!palette) return mode === 'dark' ? '#8578EC' : '#5446CC'
  return mode === 'dark' ? palette.dark[600] : palette.light[600]
})

function cardColor(p: Project): string { return p.color ?? brandFallbackColor.value ?? '#6D5FE6' }

function hexRgba(hex: string, alpha: number) {
  const r = parseInt(hex.slice(1, 3), 16)
  const g = parseInt(hex.slice(3, 5), 16)
  const b = parseInt(hex.slice(5, 7), 16)
  return `rgba(${r},${g},${b},${alpha})`
}

function darkenHex(hex: string, factor = 0.65) {
  const r = Math.round(parseInt(hex.slice(1, 3), 16) * factor)
  const g = Math.round(parseInt(hex.slice(3, 5), 16) * factor)
  const b = Math.round(parseInt(hex.slice(5, 7), 16) * factor)
  return `rgb(${r},${g},${b})`
}

function relativeDate(dateStr: string) {
  const diff = Date.now() - new Date(dateStr).getTime()
  const days = Math.floor(diff / 86400000)
  if (days === 0) return "aujourd'hui"
  if (days === 1) return 'hier'
  if (days < 7) return `il y a ${days}j`
  if (days < 30) return `il y a ${Math.floor(days / 7)} sem.`
  return `il y a ${Math.floor(days / 30)} mois`
}

function initials(name: string) {
  return name.split(' ').map(n => n[0]).join('').slice(0, 2).toUpperCase()
}

function genreChipStyle(genreId: string) {
  const cat = getCategoryForGenre(genreId)
  if (!cat) return { background: '#f3f4f6', color: '#6b7280' }
  return { background: cat.lightColor, color: cat.textColor, border: `1px solid ${cat.color}40` }
}

function wordRingDash(p: Project) {
  const circ = 113.1 // 2π × r18
  return `${Math.min(wordPct(p), 100) / 100 * circ} ${circ}`
}

// ── Paramètres projet ─────────────────────────────────────
const settingsProject = ref<Project | null>(null)

function onProjectUpdated(updated: Project) {
  const existing = projects.projects.find(p => p.id === updated.id)
  if (existing) {
    existing.title  = updated.title
    existing.genres = updated.genres
    existing.status = updated.status
    existing.color  = updated.color
  }
}

function onProjectDeleted(id: string) {
  projects.projects = projects.projects.filter(p => p.id !== id)
}
</script>

<template>
  <div class="p-6 max-w-6xl mx-auto">

    <!-- Barre de recherche & tri ─────────────────── -->
    <div class="flex items-center gap-3 mb-8">
      <div class="relative">
        <svg class="absolute left-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-gray-400 pointer-events-none"
             fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
        </svg>
        <input
          v-model="search"
          type="text"
          placeholder="Rechercher…"
          class="w-52 pl-8 pr-3 py-1.5 text-sm rounded-lg shadow-sm
                 border border-gray-200 dark:border-gray-700
                 bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-300
                 focus:outline-none focus:ring-2 focus:ring-brand-500"
        />
      </div>
      <!-- Select on mobile, button group on sm+ -->
      <select
        v-model="sort"
        class="sm:hidden ml-auto text-xs px-2 py-1.5 rounded-lg border border-gray-200 dark:border-gray-700
               bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-300
               focus:outline-none focus:ring-2 focus:ring-brand-500"
      >
        <option value="recent">Récent</option>
        <option value="title">Titre</option>
        <option value="progress">Progression</option>
      </select>
      <div class="hidden sm:flex ml-auto items-center bg-gray-100 dark:bg-gray-800 rounded-lg p-0.5">
        <button
          v-for="(label, key) in ({ recent: 'Récent', title: 'Titre', progress: 'Progression' } as const)"
          :key="key"
          class="text-xs px-3 py-1 rounded-md transition-all"
          :class="sort === key
            ? 'bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm font-medium'
            : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200'"
          @click="sort = key"
        >{{ label }}</button>
      </div>
    </div>

    <!-- Grille de projets ───────────────────────── -->
    <div v-if="projects.loading" class="text-sm text-gray-400 py-16 text-center">Chargement…</div>

    <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">

      <!-- Card projet ─────────────────────────────── -->
      <div
        v-for="p in filtered"
        :key="p.id"
        class="rounded-xl border border-gray-200 dark:border-gray-700
               bg-white dark:bg-gray-900 shadow-sm hover:shadow-md transition-all flex flex-col overflow-hidden"
        :style="{ borderTop: `4px solid ${cardColor(p)}` }"
      >
        <!-- Header avec wash de couleur ─────────── -->
        <div class="px-4 pt-3.5 pb-3 flex items-start gap-3"
             :style="{ background: hexRgba(cardColor(p), 0.06) }">

          <!-- Gauche : statut, titre, genres -->
          <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2 mb-1.5">
              <span
                class="inline-flex items-center text-[10px] font-semibold uppercase tracking-wider px-2 py-0.5 rounded-full"
                :style="{ background: hexRgba(STATUS_COLOR[p.status].bg, 0.12), color: STATUS_COLOR[p.status].bg }"
              >{{ STATUS_LABEL[p.status] }}</span>
              <button
                class="ml-auto p-1 rounded-md text-gray-400 hover:text-gray-600 dark:hover:text-gray-200
                       hover:bg-black/5 dark:hover:bg-white/10 transition-colors"
                title="Paramètres du roman"
                @click.stop="settingsProject = p"
              >
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                    d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
              </button>
            </div>

            <h2 class="font-semibold text-[15px] leading-tight text-gray-900 dark:text-gray-100">
              {{ p.title }}
            </h2>

            <!-- Chips genres colorés par univers littéraire -->
            <div v-if="p.genres?.length" class="flex flex-wrap gap-1 mt-2">
              <span
                v-for="gid in p.genres" :key="gid"
                class="text-[10px] font-medium px-2 py-0.5 rounded-full"
                :style="genreChipStyle(gid)"
              >{{ getGenreName(gid) }}</span>
            </div>
            <p v-else class="text-[11px] text-gray-300 dark:text-gray-600 mt-1.5">Aucun genre</p>
          </div>

          <!-- Droite : anneau de progression mots -->
          <div v-if="p.target_words" class="shrink-0 self-center mt-5">
            <svg width="46" height="46" viewBox="0 0 46 46" fill="none">
              <!-- Piste -->
              <circle cx="23" cy="23" r="18"
                      :stroke="hexRgba(cardColor(p), 0.18)"
                      stroke-width="5"/>
              <!-- Arc progression -->
              <circle cx="23" cy="23" r="18"
                      :stroke="cardColor(p)"
                      stroke-width="5"
                      stroke-linecap="round"
                      :stroke-dasharray="wordRingDash(p)"
                      transform="rotate(-90 23 23)"/>
              <!-- Pourcentage -->
              <text x="23" y="27.5"
                    text-anchor="middle"
                    font-size="9.5"
                    font-weight="700"
                    :fill="cardColor(p)">{{ wordPct(p) }}%</text>
            </svg>
          </div>
        </div>

        <!-- Corps ───────────────────────────────── -->
        <div class="px-4 py-3 flex-1 space-y-2.5 border-t border-gray-100 dark:border-gray-800">

          <!-- Compteur mots (compact) -->
          <p class="text-xs text-gray-500 dark:text-gray-400">
            <span class="font-semibold text-gray-800 dark:text-gray-200">
              {{ p.word_count.toLocaleString('fr-FR') }}
            </span>
            <template v-if="p.target_words">
              / {{ p.target_words.toLocaleString('fr-FR') }} mots
              <template v-if="p.target_scenes"> · {{ p.scene_count }}/{{ p.target_scenes }} scènes</template>
            </template>
            <template v-else>
              mot{{ p.word_count !== 1 ? 's' : '' }}
              <template v-if="p.scene_count"> · {{ p.scene_count }} scène{{ p.scene_count !== 1 ? 's' : '' }}</template>
            </template>
          </p>

          <!-- Dernière scène (mise en avant) -->
          <p v-if="p.last_scene_title"
             class="flex items-start gap-1.5 text-xs text-gray-500 dark:text-gray-400">
            <svg class="w-3 h-3 shrink-0 mt-0.5 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
            </svg>
            <span class="italic truncate">{{ p.last_scene_title }}</span>
          </p>

          <!-- Meta : fiches & date -->
          <div class="flex items-center justify-between text-xs text-gray-400 pt-0.5">
            <span>{{ p.cards_count }} fiche{{ p.cards_count !== 1 ? 's' : '' }}</span>
            <span>{{ relativeDate(p.updated_at) }}</span>
          </div>
        </div>

        <!-- Footer ──────────────────────────────── -->
        <div class="px-4 py-3 border-t border-gray-100 dark:border-gray-800 flex items-center justify-between gap-2">
          <div class="flex -space-x-1.5">
            <div
              v-for="m in (p.members ?? []).slice(0, 4)"
              :key="m.id"
              class="w-6 h-6 rounded-full flex items-center justify-center
                     text-[10px] font-semibold text-white ring-2 ring-white dark:ring-gray-900"
              :style="{ background: cardColor(p) }"
              :title="m.name"
            >{{ initials(m.name) }}</div>
          </div>
          <div class="flex gap-1.5">
            <button
              class="text-xs px-3 py-1.5 rounded-lg transition-colors
                     text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800"
            >Accès</button>
            <button
              class="text-xs px-3 py-1.5 rounded-lg font-medium text-white transition-opacity hover:opacity-90"
              :style="{ background: cardColor(p) }"
              @click="router.push({ name: 'editor', params: { projectId: p.id } })"
            >Ouvrir</button>
          </div>
        </div>
      </div>

      <!-- Card "+ Nouveau projet" ──────────────────── -->
      <button
        v-if="!atProjectLimit"
        class="rounded-xl border-2 border-dashed border-gray-200 dark:border-gray-700
               hover:border-brand-300 dark:hover:border-brand-600 transition-colors
               flex flex-col items-center justify-center gap-3 min-h-48 py-8
               text-gray-400 hover:text-brand-600 dark:hover:text-brand-400"
        title="Créer un nouveau projet"
        @click="openTypeSelect"
      >
        <div class="w-10 h-10 rounded-full border-2 border-current flex items-center justify-center">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
          </svg>
        </div>
        <span class="text-sm font-medium">Nouveau projet</span>
      </button>

      <!-- Card premium requis ─────────────────────── -->
      <div
        v-else
        class="rounded-xl border border-gray-200 dark:border-gray-700
               bg-gray-50 dark:bg-gray-800/50
               flex flex-col items-center justify-center gap-3 min-h-48 py-8 px-6 text-center"
      >
        <div class="w-10 h-10 rounded-full bg-amber-100 dark:bg-amber-900/30
                    flex items-center justify-center">
          <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
          </svg>
        </div>
        <div>
          <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Limite atteinte</p>
          <p class="text-xs text-gray-400 mt-0.5 leading-relaxed">
            Passez premium pour créer<br>des projets supplémentaires
          </p>
        </div>
        <button
          class="text-xs px-4 py-1.5 rounded-lg bg-amber-500 hover:bg-amber-600
                 text-white font-medium transition-colors"
        >Passer premium</button>
      </div>
    </div>

    <!-- Dialog paramètres roman ─────────────────── -->
    <ProjectSettingsDialog
      v-if="settingsProject"
      :project="settingsProject"
      @close="settingsProject = null"
      @updated="onProjectUpdated"
      @deleted="onProjectDeleted"
    />

    <!-- Modal sélection du type ─────────────────── -->
    <Transition name="fade">
      <div
        v-if="showTypeSelect"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/30 backdrop-blur-sm"
        @click.self="cancelTypeSelect"
      >
        <div class="w-full max-w-2xl bg-white dark:bg-gray-900 rounded-2xl shadow-xl p-6 mx-4">
          <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-1">Quel type de projet ?</h2>
          <p class="text-sm text-gray-500 dark:text-gray-400 mb-5">Choisissez le format qui correspond à votre projet d'écriture.</p>

          <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
            <button
              v-for="ct in contentTypes"
              :key="ct.id"
              class="relative flex flex-col items-start gap-2 rounded-xl border-2 p-4 text-left transition-all"
              :class="ct.is_premium && !isPremium
                ? 'border-gray-200 dark:border-gray-700 opacity-75 cursor-not-allowed'
                : 'border-gray-200 dark:border-gray-700 hover:border-brand-400 dark:hover:border-brand-500 hover:shadow-sm cursor-pointer'"
              @click="selectType(ct)"
            >
              <!-- Cadenas premium -->
              <span
                v-if="ct.is_premium && !isPremium"
                class="absolute top-3 right-3 flex items-center gap-1 text-[10px] font-semibold text-amber-500"
                title="Premium requis"
              >
                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M12 1C8.676 1 6 3.676 6 7v1H4a1 1 0 00-1 1v13a1 1 0 001 1h16a1 1 0 001-1V9a1 1 0 00-1-1h-2V7c0-3.324-2.676-6-6-6zm0 2c2.276 0 4 1.724 4 4v1H8V7c0-2.276 1.724-4 4-4zm0 10a2 2 0 110 4 2 2 0 010-4z"/>
                </svg>
                Premium
              </span>

              <div class="text-base font-semibold text-gray-900 dark:text-gray-100">
                {{ ct.short_name || ct.name }}
              </div>
              <p v-if="ct.description" class="text-xs text-gray-500 dark:text-gray-400 leading-snug line-clamp-2">{{ ct.description }}</p>
            </button>
          </div>

          <div class="mt-5 text-right">
            <button
              class="px-4 text-sm text-gray-500 hover:text-gray-700 dark:hover:text-gray-300"
              @click="cancelTypeSelect"
            >Annuler</button>
          </div>
        </div>
      </div>
    </Transition>

    <!-- Modal création ──────────────────────────── -->
    <Transition name="fade">
      <div
        v-if="showNewForm"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/30 backdrop-blur-sm"
        @click.self="cancelNewForm"
      >
        <div class="w-full max-w-md bg-white dark:bg-gray-900 rounded-2xl shadow-xl p-6 mx-4">
          <div class="flex items-center gap-2 mb-4">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
              Nouveau {{ selectedContentType?.short_name || selectedContentType?.name || 'projet' }}
            </h2>
            <span
              v-if="selectedContentType && contentTypes.length > 1"
              class="text-xs px-2 py-0.5 rounded-full bg-brand-50 dark:bg-brand-950 text-brand-600 dark:text-brand-400 cursor-pointer hover:bg-brand-100"
              @click="cancelNewForm(); showTypeSelect = true"
            >Changer</span>
          </div>
          <div class="space-y-3">
            <div>
              <label class="block text-xs font-medium text-gray-500 mb-1">Titre *</label>
              <input
                v-model="newTitle"
                type="text"
                placeholder="Les Ombres de Verre…"
                autofocus
                class="w-full rounded-lg border border-gray-300 dark:border-gray-700
                       bg-white dark:bg-gray-800 text-sm text-gray-900 dark:text-gray-100
                       px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-500"
                @keyup.enter="createProject"
                @keyup.escape="cancelNewForm"
              />
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-500 mb-1">Genres</label>
              <GenreSelector v-model="newGenres" />
            </div>
          </div>
          <div class="flex gap-2 mt-5">
            <button
              :disabled="creating || !newTitle.trim()"
              class="flex-1 bg-brand-600 hover:bg-brand-800 disabled:opacity-50
                     text-white text-sm font-medium rounded-lg py-2 transition-colors"
              @click="createProject"
            >{{ creating ? 'Création…' : 'Créer et ouvrir' }}</button>
            <button
              class="px-4 text-sm text-gray-500 hover:text-gray-700 dark:hover:text-gray-300"
              @click="cancelNewForm"
            >Annuler</button>
          </div>
        </div>
      </div>
    </Transition>
  </div>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.15s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
