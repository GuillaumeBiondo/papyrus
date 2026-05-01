<script setup lang="ts">
defineProps<{
  title: string
  message: string
  counts: { label: string; value: number }[]
  loading?: boolean
}>()

const emit = defineEmits<{
  confirm: []
  cancel: []
}>()
</script>

<template>
  <Teleport to="body">
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm"
         @click.self="emit('cancel')">
      <div class="bg-white dark:bg-gray-900 rounded-xl shadow-2xl w-full max-w-sm border border-gray-200 dark:border-gray-700">

        <!-- Header -->
        <div class="flex items-start gap-3 p-5 border-b border-gray-100 dark:border-gray-800">
          <div class="mt-0.5 shrink-0 w-9 h-9 rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center">
            <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
            </svg>
          </div>
          <div>
            <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ title }}</h3>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ message }}</p>
          </div>
        </div>

        <!-- Comptage des éléments supprimés -->
        <div v-if="counts.length" class="px-5 py-3 space-y-1.5">
          <p class="text-xs font-medium text-gray-600 dark:text-gray-300 mb-2">Sera supprimé :</p>
          <div v-for="item in counts" :key="item.label"
               class="flex items-center justify-between text-xs">
            <span class="text-gray-500 dark:text-gray-400">{{ item.label }}</span>
            <span class="font-medium text-red-500 tabular-nums">{{ item.value }}</span>
          </div>
        </div>

        <!-- Actions -->
        <div class="flex gap-2 p-4 border-t border-gray-100 dark:border-gray-800">
          <button
            class="flex-1 px-4 py-2 text-xs rounded-lg border border-gray-200 dark:border-gray-700
                   text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors"
            @click="emit('cancel')"
          >Annuler</button>
          <button
            :disabled="loading"
            class="flex-1 px-4 py-2 text-xs rounded-lg bg-red-500 hover:bg-red-600
                   text-white font-medium transition-colors disabled:opacity-50"
            @click="emit('confirm')"
          >
            {{ loading ? 'Suppression…' : 'Supprimer définitivement' }}
          </button>
        </div>

      </div>
    </div>
  </Teleport>
</template>
