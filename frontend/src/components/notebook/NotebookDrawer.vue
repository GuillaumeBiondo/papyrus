<script setup lang="ts">
import { ref, watch } from 'vue'
import { useNotebookStore } from '@/stores/notebook.store'

const notebook = useNotebookStore()
const newBody = ref('')

watch(
  () => notebook.drawerOpen,
  (open) => {
    if (open && !notebook.entries.length) {
      notebook.fetchAll()
    }
  },
)

async function submitNew() {
  if (!newBody.value.trim()) return
  await notebook.save({ body: newBody.value })
  newBody.value = ''
}
</script>

<template>
  <Transition name="drawer">
    <div
      v-if="notebook.drawerOpen"
      class="absolute inset-y-0 right-0 w-80 bg-white dark:bg-gray-900
             border-l border-gray-200 dark:border-gray-700
             shadow-xl flex flex-col z-40"
    >
      <div class="flex items-center justify-between px-4 h-12 border-b
                  border-gray-200 dark:border-gray-700 shrink-0">
        <h2 class="font-medium text-gray-900 dark:text-gray-100 text-sm">Carnet</h2>
        <div class="flex items-center gap-2">
          <button class="btn-ghost text-xs text-brand-600 dark:text-brand-400" @click="notebook.openNew()">
            + Note
          </button>
          <button class="btn-ghost text-gray-500" @click="notebook.toggleDrawer()">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
      </div>

      <div v-if="notebook.composing" class="p-3 border-b border-gray-100 dark:border-gray-800">
        <textarea
          v-model="newBody"
          placeholder="Nouvelle note…"
          rows="3"
          class="w-full text-sm border border-gray-200 dark:border-gray-700 rounded-md p-2
                 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100
                 focus:outline-none focus:ring-2 focus:ring-brand-400 resize-none"
        />
        <div class="flex justify-end gap-2 mt-2">
          <button class="text-xs text-gray-500 px-2 py-1" @click="notebook.composing = false">Annuler</button>
          <button class="text-xs bg-brand-600 text-white rounded px-3 py-1 hover:bg-brand-800" @click="submitNew">
            Enregistrer
          </button>
        </div>
      </div>

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
    </div>
  </Transition>
</template>

<style scoped>
.drawer-enter-active, .drawer-leave-active { transition: transform 0.2s ease; }
.drawer-enter-from, .drawer-leave-to { transform: translateX(100%); }
</style>
