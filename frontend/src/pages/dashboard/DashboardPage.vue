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

// ── Création ──────────────────────────────────────────────
const showNewForm = ref(false)
const newTitle = ref('')
const newGenres = ref<string[]>([])
const creating = ref(false)

onMounted(() => { projects.fetchAll(); appConfig.fetch() })

async function createProject() {
  if (!newTitle.value.trim()) return
  creating.value = true
  try {
    const project = await projects.create({
      title: newTitle.value.trim(),
      genres: newGenres.value.length > 0 ? newGenres.value : undefined,
    })
    showNewForm.value = false
    newTitle.value = ''
    newGenres.value = []
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

function headerGradient(p: Project): string {
  const dark = theme.applied === 'dark'
  const seen = new Map<string, string>()
  for (const gid of (p.genres ?? [])) {
    const cat = getCategoryForGenre(gid)
    if (cat && !seen.has(cat.id)) seen.set(cat.id, cat.color)
  }
  const cols = [...seen.values()]
  if (cols.length === 0) {
    const c = cardColor(p)
    return `linear-gradient(135deg, ${hexRgba(c, dark ? 0.55 : 0.18)}, ${hexRgba(c, dark ? 0.20 : 0.06)})`
  }
  if (cols.length === 1) return `linear-gradient(135deg, ${hexRgba(cols[0]!, dark ? 0.72 : 0.34)}, ${hexRgba(cols[0]!, dark ? 0.28 : 0.10)})`
  return `linear-gradient(135deg, ${hexRgba(cols[0]!, dark ? 0.70 : 0.36)} 0%, ${hexRgba(cols[1]!, dark ? 0.48 : 0.22)} 100%)`
}

const waveCache = ref<Record<string, string>>({})

function generateWave(): string {
  const r = () => Math.random()
  // 3 peaks with random amplitude (5–14px) and random y-offsets
  const amp = () => (5 + r() * 9) * (r() > 0.5 ? 1 : -1)
  const yBase = 14
  const y0 = yBase + (r() - 0.5) * 10
  const y1 = yBase + (r() - 0.5) * 10
  const y2 = yBase + (r() - 0.5) * 10
  const y3 = yBase + (r() - 0.5) * 10
  const f = (n: number) => Math.max(1, Math.min(26, n)).toFixed(1)
  return (
    `M0,${f(y0)} ` +
    `C70,${f(y0 + amp())} 120,${f(y1 + amp())} 200,${f(y1)} ` +
    `C270,${f(y1 + amp())} 330,${f(y2 + amp())} 400,${f(y3)} ` +
    `L400,28 L0,28 Z`
  )
}

function wavePath(p: Project): string {
  if (!waveCache.value[p.id]) waveCache.value[p.id] = generateWave()
  return waveCache.value[p.id]!
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
  if (!cat) return { background: 'rgba(255,255,255,0.82)', color: '#6b7280', border: '1.5px solid rgba(107,114,128,0.35)' }
  return {
    background: 'rgba(255,255,255,0.82)',
    color: cat.textColor,
    border: `1.5px solid ${cat.color}70`,
  }
}

function wordRingDash(p: Project) {
  const circ = 113.1 // 2π × r18
  const pct = Math.max(wordPct(p), 4) // arc minimum visible
  return `${Math.min(pct, 100) / 100 * circ} ${circ}`
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
                 border border-gray-200 dark:border-gray-600
                 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200
                 placeholder:text-gray-400 dark:placeholder:text-gray-500
                 focus:outline-none focus:ring-2 focus:ring-brand-500"
        />
      </div>
      <!-- Select on mobile, button group on sm+ -->
      <select
        v-model="sort"
        class="sm:hidden ml-auto text-xs px-2 py-1.5 rounded-lg border border-gray-200 dark:border-gray-600
               bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200
               focus:outline-none focus:ring-2 focus:ring-brand-500"
      >
        <option value="recent">Récent</option>
        <option value="title">Titre</option>
        <option value="progress">Progression</option>
      </select>
      <div class="hidden sm:flex ml-auto items-center bg-gray-100 dark:bg-gray-700 rounded-lg p-0.5">
        <button
          v-for="(label, key) in ({ recent: 'Récent', title: 'Titre', progress: 'Progression' } as const)"
          :key="key"
          class="text-xs px-3 py-1 rounded-md transition-all"
          :class="sort === key
            ? 'bg-white dark:bg-gray-500 text-gray-900 dark:text-white shadow-sm font-medium'
            : 'text-gray-500 dark:text-gray-300 hover:text-gray-700 dark:hover:text-white'"
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
        class="rounded-xl border border-gray-200 dark:border-gray-600
               bg-white dark:bg-gray-800 shadow-sm hover:shadow-md transition-shadow flex flex-col overflow-hidden"
      >
        <!-- Header — gradient depuis les univers du roman ── -->
        <div class="relative px-4 pt-3.5 pb-7 flex items-start gap-3"
             :style="{ background: headerGradient(p) }">

          <!-- Gauche : statut, titre, genres -->
          <div class="flex-1 min-w-0">
            <!-- Pastille statut — toujours visible sur mobile, expand au hover desktop -->
            <div class="group/status flex items-center gap-1.5 mb-1.5 cursor-default select-none">
              <span
                class="w-2 h-2 rounded-full shrink-0"
                :style="{ background: STATUS_COLOR[p.status].bg }"
              />
              <span
                class="overflow-hidden max-w-full sm:max-w-0 sm:group-hover/status:max-w-[6rem]
                       transition-[max-width] duration-200 ease-out
                       text-[10px] font-semibold uppercase tracking-wider whitespace-nowrap"
                :style="{ color: STATUS_COLOR[p.status].bg }"
              >{{ STATUS_LABEL[p.status] }}</span>
            </div>

            <h2 class="font-semibold text-[15px] leading-tight text-gray-900 dark:text-gray-100">
              {{ p.title }}
            </h2>

            <!-- Chips genres -->
            <div v-if="p.genres?.length" class="flex flex-wrap gap-1 mt-2">
              <span
                v-for="gid in p.genres" :key="gid"
                class="text-[10px] font-medium px-2 py-0.5 rounded-full"
                :style="genreChipStyle(gid)"
              >{{ getGenreName(gid) }}</span>
            </div>
          </div>

          <!-- Anneau de progression -->
          <div
            v-if="p.target_words"
            class="shrink-0 self-center mt-4 cursor-default"
            :title="`${p.word_count.toLocaleString('fr-FR')} / ${p.target_words.toLocaleString('fr-FR')} mots`"
          >
            <svg width="46" height="46" viewBox="0 0 46 46" fill="none">
              <circle cx="23" cy="23" r="18"
                      :stroke="hexRgba(cardColor(p), 0.28)"
                      stroke-width="5"/>
              <circle cx="23" cy="23" r="18"
                      :stroke="cardColor(p)"
                      stroke-width="5"
                      stroke-linecap="round"
                      :stroke-dasharray="wordRingDash(p)"
                      transform="rotate(-90 23 23)"/>
              <text x="23" y="27.5"
                    text-anchor="middle"
                    font-size="9"
                    font-weight="700"
                    :fill="cardColor(p)"
              >{{ wordPct(p) }}%</text>
            </svg>
          </div>

          <!-- Vague décorative en bas du header -->
          <svg
            class="absolute bottom-0 left-0 w-full text-white dark:text-gray-800"
            style="height:28px"
            viewBox="0 0 400 28"
            preserveAspectRatio="none"
            fill="currentColor"
          >
            <path :d="wavePath(p)" />
          </svg>
        </div>

        <!-- Corps ───────────────────────────────── -->
        <div class="px-4 py-3 flex-1 space-y-2">

          <!-- Stats : chapitres + scènes + fiches (+ mots si pas d'anneau) -->
          <p class="text-xs text-gray-500 dark:text-gray-300">
            <template v-if="p.chapters_count">
              <span class="font-semibold text-gray-700 dark:text-gray-100">{{ p.chapters_count }}</span> chap. ·
            </template>
            <template v-if="p.target_scenes">
              <span class="font-semibold text-gray-700 dark:text-gray-100">{{ p.scene_count }}/{{ p.target_scenes }}</span> scènes ·
            </template>
            <template v-else-if="p.scene_count">
              <span class="font-semibold text-gray-700 dark:text-gray-100">{{ p.scene_count }}</span> scène{{ p.scene_count !== 1 ? 's' : '' }} ·
            </template>
            <span class="font-semibold text-gray-700 dark:text-gray-100">{{ p.cards_count }}</span> fiche{{ p.cards_count !== 1 ? 's' : '' }}
            <template v-if="!p.target_words && p.word_count">
              · <span class="font-semibold text-gray-700 dark:text-gray-100">{{ p.word_count.toLocaleString('fr-FR') }}</span> mots
            </template>
          </p>

          <!-- Dernière scène + date sur la même ligne -->
          <div class="flex items-center gap-1.5 text-xs text-gray-400 dark:text-gray-300 min-w-0">
            <svg v-if="p.last_scene_title" class="w-3 h-3 shrink-0 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
            </svg>
            <span v-if="p.last_scene_title" class="italic truncate flex-1 min-w-0">{{ p.last_scene_title }}</span>
            <span v-else class="flex-1 text-gray-300 dark:text-gray-500 text-[11px]">Aucune scène</span>
            <span class="shrink-0 text-[11px] text-gray-400 dark:text-gray-400">{{ relativeDate(p.updated_at) }}</span>
          </div>
        </div>

        <!-- Footer ──────────────────────────────── -->
        <div class="px-4 py-2.5 border-t border-gray-100 dark:border-gray-700 flex items-center gap-2">
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
          <div class="flex gap-1 ml-auto">
            <button
              class="px-2.5 py-1.5 rounded-lg text-gray-500 dark:text-gray-300
                     hover:text-gray-800 dark:hover:text-white
                     hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors
                     text-base leading-none font-black tracking-widest"
              title="Paramètres du roman"
              @click.stop="settingsProject = p"
            >···</button>
            <button
              class="text-xs px-3 py-1.5 rounded-lg font-medium text-white transition-opacity hover:opacity-90"
              :style="{ background: cardColor(p) }"
              @click="router.push({ name: 'editor', params: { projectId: p.id } })"
            >Ouvrir</button>
          </div>
        </div>
      </div>

      <!-- Card "+ Nouveau roman" ──────────────────── -->
      <button
        v-if="!atProjectLimit"
        class="rounded-xl border-2 border-dashed border-gray-300 dark:border-gray-600
               hover:border-brand-300 dark:hover:border-brand-500 transition-colors
               flex flex-col items-center justify-center gap-3 min-h-48 py-8
               text-gray-400 dark:text-gray-500 hover:text-brand-600 dark:hover:text-brand-400"
        title="Créer un nouveau projet"
        @click="showNewForm = true"
      >
        <div class="w-10 h-10 rounded-full border-2 border-current flex items-center justify-center">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
          </svg>
        </div>
        <span class="text-sm font-medium">Nouveau roman</span>
      </button>

      <!-- Card premium requis ─────────────────────── -->
      <div
        v-else
        class="rounded-xl border border-gray-200 dark:border-gray-600
               bg-gray-50 dark:bg-gray-800
               flex flex-col items-center justify-center gap-3 min-h-48 py-8 px-6 text-center"
      >
        <div class="w-10 h-10 rounded-full bg-amber-100 dark:bg-amber-900/40
                    flex items-center justify-center">
          <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
          </svg>
        </div>
        <div>
          <p class="text-sm font-medium text-gray-700 dark:text-gray-200">Limite atteinte</p>
          <p class="text-xs text-gray-400 dark:text-gray-400 mt-0.5 leading-relaxed">
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

    <!-- Modal création ──────────────────────────── -->
    <Transition name="fade">
      <div
        v-if="showNewForm"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/30 backdrop-blur-sm"
        @click.self="showNewForm = false"
      >
        <div class="w-full max-w-md bg-white dark:bg-gray-900 rounded-2xl shadow-xl p-6 mx-4">
          <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Nouveau roman</h2>
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
                @keyup.escape="showNewForm = false"
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
              @click="showNewForm = false"
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
