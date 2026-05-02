<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useProjectsStore } from '@/stores/projects.store'
import ProjectSettingsDialog from '@/components/dashboard/ProjectSettingsDialog.vue'
import type { Project } from '@/types'

const router = useRouter()
const projects = useProjectsStore()

// ── Création ──────────────────────────────────────────────
const showNewForm = ref(false)
const newTitle = ref('')
const newGenre = ref('')
const creating = ref(false)

onMounted(() => projects.fetchAll())

async function createProject() {
  if (!newTitle.value.trim()) return
  creating.value = true
  try {
    const project = await projects.create({
      title: newTitle.value.trim(),
      genre: newGenre.value.trim() || undefined,
    })
    showNewForm.value = false
    newTitle.value = ''
    newGenre.value = ''
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
    list = list.filter(p => p.title.toLowerCase().includes(q) || p.genre?.toLowerCase().includes(q))
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
  draft:       { bg: 'rgba(156,163,175,0.18)', text: '#6b7280' },
  in_progress: { bg: 'rgba(59,130,246,0.15)',  text: '#2563eb' },
  revision:    { bg: 'rgba(245,158,11,0.15)',  text: '#d97706' },
  complete:    { bg: 'rgba(34,197,94,0.15)',   text: '#16a34a' },
}

function wordPct(p: Project) {
  return p.target_words ? Math.round((p.word_count / p.target_words) * 100) : 0
}
function scenePct(p: Project) {
  return p.target_scenes ? Math.round((p.scene_count / p.target_scenes) * 100) : 0
}

function cardColor(p: Project) { return p.color ?? '#534AB7' }

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

// ── Paramètres projet ─────────────────────────────────────
const settingsProject = ref<Project | null>(null)

function onProjectUpdated(updated: Project) {
  const existing = projects.projects.find(p => p.id === updated.id)
  if (existing) {
    existing.title  = updated.title
    existing.genre  = updated.genre
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
    <div class="flex items-center gap-3 mb-6">
      <input
        v-model="search"
        type="text"
        placeholder="Rechercher…"
        class="w-44 text-sm rounded-lg border border-gray-300 dark:border-gray-700
               bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-300
               px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-brand-500"
      />
      <div class="ml-auto flex items-center gap-1">
        <button
          v-for="(label, key) in ({ recent: 'Récent', title: 'Titre', progress: 'Progression' } as const)"
          :key="key"
          class="text-sm px-3 py-1.5 rounded-lg transition-colors"
          :class="sort === key
            ? 'bg-gray-900 dark:bg-gray-100 text-white dark:text-gray-900 font-medium'
            : 'text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-800'"
          @click="sort = key"
        >{{ label }}</button>
      </div>
    </div>

    <!-- Grille de projets ───────────────────────── -->
    <div v-if="projects.loading" class="text-sm text-gray-400 py-16 text-center">Chargement…</div>

    <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">

      <!-- Card projet ─────────────────────────────── -->
      <div
        v-for="p in filtered"
        :key="p.id"
        class="rounded-xl overflow-hidden border border-gray-200 dark:border-gray-700
               bg-white dark:bg-gray-900 shadow-sm hover:shadow-md transition-shadow flex flex-col"
      >
        <!-- Header coloré -->
        <div
          class="px-4 pt-4 pb-3 border-l-[8px]"
          :style="{
            background: hexRgba(cardColor(p), 0.08),
            borderLeftColor: darkenHex(cardColor(p), 0.88),
          }"
        >
          <div class="flex items-start justify-between mb-1">
            <h2
              class="font-semibold text-base leading-tight"
              :style="{ color: cardColor(p) }"
            >{{ p.title }}</h2>
            <div class="flex items-center gap-1 shrink-0 ml-2 mt-0.5">
              <span
                class="text-xs rounded-full px-2 py-0.5 font-medium"
                :style="{
                  background: STATUS_COLOR[p.status].bg,
                  color: STATUS_COLOR[p.status].text,
                }"
              >{{ STATUS_LABEL[p.status] }}</span>
              <button
                class="p-1 rounded-md opacity-60 hover:opacity-100 transition-opacity"
                :style="{ color: cardColor(p) }"
                title="Paramètres du roman"
                @click.stop="settingsProject = p"
              >
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
              </button>
            </div>
          </div>
          <p class="text-xs" :style="{ color: cardColor(p), opacity: 0.7 }">
            {{ p.genre ?? '—' }}
          </p>
        </div>

        <!-- Stats ────────────────────────────────── -->
        <div class="px-4 py-3 flex-1 space-y-2.5">
          <!-- Mots -->
          <div v-if="p.target_words">
            <div class="flex justify-between text-xs text-gray-500 dark:text-gray-400 mb-1">
              <span>Mots</span>
              <span :style="{ color: cardColor(p) }" class="font-medium">{{ wordPct(p) }}%</span>
            </div>
            <div class="h-1.5 bg-gray-100 dark:bg-gray-800 rounded-full overflow-hidden">
              <div
                class="h-full rounded-full transition-all"
                :style="{ width: wordPct(p) + '%', background: cardColor(p) }"
              />
            </div>
            <p class="text-xs text-gray-400 mt-0.5">
              {{ p.word_count.toLocaleString('fr-FR') }} / {{ p.target_words.toLocaleString('fr-FR') }}
            </p>
          </div>
          <div v-else class="text-xs text-gray-400">
            {{ p.word_count.toLocaleString('fr-FR') }} mot{{ p.word_count !== 1 ? 's' : '' }}
          </div>

          <!-- Scènes -->
          <div v-if="p.target_scenes">
            <div class="flex justify-between text-xs text-gray-500 dark:text-gray-400 mb-1">
              <span>Scènes</span>
              <span class="text-gray-600 dark:text-gray-400">{{ p.scene_count }}/{{ p.target_scenes }}</span>
            </div>
            <div class="h-1.5 bg-gray-100 dark:bg-gray-800 rounded-full overflow-hidden">
              <div
                class="h-full rounded-full transition-all"
                :style="{ width: scenePct(p) + '%', background: cardColor(p), opacity: 0.6 }"
              />
            </div>
          </div>

          <!-- Fiches & date -->
          <div class="flex items-center justify-between text-xs text-gray-400 pt-1">
            <span>{{ p.cards_count }} fiche{{ p.cards_count !== 1 ? 's' : '' }}</span>
            <span>modifié {{ relativeDate(p.updated_at) }}</span>
          </div>

          <!-- Dernière scène -->
          <p v-if="p.last_scene_title" class="text-xs text-gray-400">
            Dernière scène : <span class="italic text-gray-600 dark:text-gray-400">{{ p.last_scene_title }}</span>
          </p>
        </div>

        <!-- Footer ────────────────────────────────── -->
        <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-800 flex items-center justify-between">
          <!-- Avatars membres -->
          <div class="flex -space-x-1.5">
            <div
              v-for="m in (p.members ?? []).slice(0, 4)"
              :key="m.id"
              class="w-7 h-7 rounded-full flex items-center justify-center
                     text-xs font-semibold text-white ring-2 ring-white dark:ring-gray-900"
              :style="{ background: cardColor(p) }"
              :title="m.name"
            >{{ initials(m.name) }}</div>
          </div>

          <div class="flex gap-2">
            <button
              class="text-xs px-3 py-1.5 rounded-lg border transition-colors
                     text-gray-500 dark:text-gray-400 border-gray-300 dark:border-gray-700
                     hover:bg-gray-50 dark:hover:bg-gray-800"
            >Accès</button>
            <button
              class="text-xs px-3 py-1.5 rounded-lg border font-medium transition-colors"
              :style="{
                borderColor: cardColor(p),
                color: cardColor(p),
              }"
              @click="router.push({ name: 'editor', params: { projectId: p.id } })"
            >Ouvrir</button>
          </div>
        </div>
      </div>

      <!-- Card "+ Nouveau roman" ──────────────────── -->
      <button
        class="rounded-xl border-2 border-dashed border-gray-300 dark:border-gray-700
               hover:border-brand-300 dark:hover:border-brand-600
               text-gray-400 hover:text-brand-600 transition-colors
               flex flex-col items-center justify-center gap-2 min-h-48 py-8"
        @click="showNewForm = true"
      >
        <span class="text-2xl leading-none">+</span>
        <span class="text-sm">Nouveau roman</span>
      </button>
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
              <label class="block text-xs font-medium text-gray-500 mb-1">Genre</label>
              <input
                v-model="newGenre"
                type="text"
                placeholder="Fantasy, Thriller, Romance…"
                class="w-full rounded-lg border border-gray-300 dark:border-gray-700
                       bg-white dark:bg-gray-800 text-sm text-gray-900 dark:text-gray-100
                       px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-500"
                @keyup.escape="showNewForm = false"
              />
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
