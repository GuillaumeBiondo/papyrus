<script setup lang="ts">
import { onMounted } from 'vue'
import { useNotebookStore } from '@/stores/notebook.store'

const notebook = useNotebookStore()

onMounted(() => notebook.fetchAll())
</script>

<template>
  <div class="p-6 max-w-3xl mx-auto">
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-100">Carnet</h1>
      <button
        class="bg-brand-600 hover:bg-brand-800 text-white text-sm rounded-lg px-4 py-2 transition-colors"
        @click="notebook.openNew()"
      >
        + Note
      </button>
    </div>

    <div v-if="!notebook.entries.length" class="text-sm text-gray-400 text-center py-16">
      Carnet vide.
    </div>

    <div v-else class="space-y-3">
      <div
        v-for="entry in notebook.entries"
        :key="entry.id"
        class="border border-gray-300 dark:border-gray-700 rounded-lg p-4
               bg-white dark:bg-gray-900 cursor-pointer hover:shadow-sm transition-shadow"
        @click="notebook.activeEntry = entry"
      >
        <h2 class="text-sm font-medium text-gray-900 dark:text-gray-100">
          {{ entry.title || 'Sans titre' }}
        </h2>
        <p class="text-xs text-gray-400 mt-1 line-clamp-2">{{ entry.body }}</p>
      </div>
    </div>
  </div>
</template>
