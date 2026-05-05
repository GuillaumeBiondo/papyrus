<script setup lang="ts">
import { ref, watch } from 'vue'
import { useNotebookStore } from '@/stores/notebook.store'
import { projectsService } from '@/services/projects.service'
import type { Project } from '@/types'
import VoiceRecorder from '@/components/shared/VoiceRecorder.vue'

const notebook = useNotebookStore()

// Création
const newTitle = ref('')
const newBody = ref('')

// Édition
const editTitle = ref('')
const editBody = ref('')
const editProjectId = ref<string | null>(null)
const saving = ref(false)

// Projets pour le sélecteur
const projects = ref<Project[]>([])
let projectsLoaded = false

async function loadProjects() {
  if (projectsLoaded) return
  const res = await projectsService.index()
  projects.value = res.data
  projectsLoaded = true
}

watch(
  () => notebook.activeEntry,
  (entry) => {
    if (entry) {
      editTitle.value = entry.title ?? ''
      editBody.value = entry.body
      editProjectId.value = entry.project_id
      loadProjects()
    }
  },
)

watch(
  () => notebook.drawerOpen,
  (open) => {
    if (open) notebook.fetchAll({ free: true })
  },
)

async function submitNew() {
  if (!newBody.value.trim()) return
  await notebook.save({
    title: newTitle.value.trim() || undefined,
    body: newBody.value,
  })
  newTitle.value = ''
  newBody.value = ''
}

function cancelNew() {
  notebook.composing = false
  newTitle.value = ''
  newBody.value = ''
}

async function submitEdit() {
  saving.value = true
  try {
    if (editProjectId.value) {
      await notebook.transfer(notebook.activeEntry!.id, editProjectId.value)
    } else {
      await notebook.save({
        title: editTitle.value.trim() || null,
        body: editBody.value,
      })
    }
  } finally {
    saving.value = false
  }
}

async function deleteEntry() {
  if (!notebook.activeEntry) return
  await notebook.remove(notebook.activeEntry.id)
}
</script>

<template>
  <Transition name="drawer">
    <div
      v-if="notebook.drawerOpen"
      class="absolute inset-y-0 right-0 w-80 bg-white dark:bg-gray-900
             border-l border-gray-300 dark:border-gray-700
             shadow-xl flex flex-col z-40"
    >
      <!-- En-tête -->
      <div class="flex items-center justify-between px-4 h-12 border-b
                  border-gray-300 dark:border-gray-700 shrink-0">
        <template v-if="notebook.activeEntry">
          <button
            class="flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-800
                   dark:hover:text-gray-200 transition-colors"
            @click="notebook.activeEntry = null"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Retour
          </button>
          <button
            class="text-gray-400 hover:text-red-500 transition-colors"
            title="Supprimer cette note"
            @click="deleteEntry"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
            </svg>
          </button>
        </template>
        <template v-else>
          <h2 class="font-medium text-gray-900 dark:text-gray-100 text-sm">Carnet</h2>
          <div class="flex items-center gap-2">
            <button class="btn-ghost text-xs text-brand-600 dark:text-brand-400" @click="notebook.openNew()">
              + Note
            </button>
            <button class="btn-ghost text-gray-500" @click="notebook.toggleDrawer()">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
              </svg>
            </button>
          </div>
        </template>
      </div>

      <!-- Vue édition -->
      <div v-if="notebook.activeEntry" class="flex-1 flex flex-col overflow-y-auto p-4 gap-3">
        <input
          v-model="editTitle"
          type="text"
          placeholder="Titre (optionnel)"
          class="w-full text-sm border border-gray-300 dark:border-gray-700 rounded-md px-3 py-2
                 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100
                 focus:outline-none focus:ring-2 focus:ring-brand-400"
        />
        <div class="relative">
          <textarea
            v-model="editBody"
            rows="10"
            placeholder="Corps de la note…"
            class="w-full text-sm border border-gray-300 dark:border-gray-700 rounded-md p-3
                   bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100
                   focus:outline-none focus:ring-2 focus:ring-brand-400 resize-none"
          />
          <div class="absolute bottom-2 right-2">
            <VoiceRecorder @chunk="editBody += (editBody && !editBody.endsWith(' ') ? ' ' : '') + $event" />
          </div>
        </div>

        <!-- Affectation à un roman -->
        <div>
          <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5">Affecter à un roman</p>
          <select
            v-model="editProjectId"
            class="w-full text-sm border border-gray-300 dark:border-gray-700 rounded-md px-3 py-2
                   bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100
                   focus:outline-none focus:ring-2 focus:ring-brand-400"
          >
            <option :value="null">— Aucun —</option>
            <option v-for="p in projects" :key="p.id" :value="p.id">{{ p.title }}</option>
          </select>
          <p v-if="editProjectId" class="text-xs text-amber-600 dark:text-amber-400 mt-1.5 leading-relaxed">
            Cette note sera déplacée vers le roman et disparaîtra du carnet.
          </p>
        </div>

        <button
          :disabled="saving || !editBody.trim()"
          class="w-full disabled:opacity-40 text-white text-sm rounded-md py-2 transition-colors"
          :class="editProjectId
            ? 'bg-emerald-600 hover:bg-emerald-700'
            : 'bg-brand-600 hover:bg-brand-800'"
          @click="submitEdit"
        >
          <span v-if="saving">Enregistrement…</span>
          <span v-else-if="editProjectId">Transférer vers ce roman</span>
          <span v-else>Enregistrer</span>
        </button>
      </div>

      <!-- Vue liste -->
      <template v-else>
        <!-- Formulaire nouvelle note -->
        <div v-if="notebook.composing" class="p-3 border-b border-gray-200 dark:border-gray-800 space-y-2 shrink-0">
          <input
            v-model="newTitle"
            type="text"
            placeholder="Titre (optionnel)"
            class="w-full text-sm border border-gray-300 dark:border-gray-700 rounded-md px-3 py-1.5
                   bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100
                   focus:outline-none focus:ring-2 focus:ring-brand-400"
          />
          <textarea
            v-model="newBody"
            placeholder="Nouvelle note…"
            rows="3"
            class="w-full text-sm border border-gray-300 dark:border-gray-700 rounded-md p-2
                   bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100
                   focus:outline-none focus:ring-2 focus:ring-brand-400 resize-none"
          />
          <div class="flex items-center justify-between">
            <VoiceRecorder @chunk="newBody += (newBody && !newBody.endsWith(' ') ? ' ' : '') + $event" />
            <div class="flex gap-2">
              <button class="text-xs text-gray-500 px-2 py-1" @click="cancelNew">Annuler</button>
              <button
                :disabled="!newBody.trim()"
                class="text-xs bg-brand-600 disabled:opacity-40 text-white rounded px-3 py-1 hover:bg-brand-700"
                @click="submitNew"
              >
                Enregistrer
              </button>
            </div>
          </div>
        </div>

        <!-- Liste des notes -->
        <div class="flex-1 overflow-y-auto">
          <div v-if="!notebook.entries.length" class="p-4 text-sm text-gray-400 text-center mt-8">
            Aucune note pour le moment.
          </div>
          <button
            v-for="entry in notebook.entries"
            :key="entry.id"
            class="w-full text-left px-4 py-3 border-b border-gray-50 dark:border-gray-800
                   hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors"
            @click="notebook.activeEntry = entry"
          >
            <p class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">
              {{ entry.title || 'Sans titre' }}
            </p>
            <p class="text-xs text-gray-400 truncate mt-0.5">{{ entry.body.slice(0, 60) }}</p>
          </button>
        </div>
      </template>
    </div>
  </Transition>
</template>

<style scoped>
.drawer-enter-active, .drawer-leave-active { transition: transform 0.2s ease; }
.drawer-enter-from, .drawer-leave-to { transform: translateX(100%); }
</style>
