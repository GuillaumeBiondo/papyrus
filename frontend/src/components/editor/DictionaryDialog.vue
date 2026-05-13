<script setup lang="ts">
const TYPE_LABELS: Record<string, string> = {
  definition:    'Définition',
  synonymes:     'Synonymes',
  metaphores:    'Métaphores',
  champ_lexical: 'Champ lexical',
  registre:      'Registre de langue',
}

defineProps<{
  type: string
  query: string
  loading: boolean
  error: string | null
  items: { text: string; detail: string }[]
}>()

const emit = defineEmits<{ close: [] }>()

async function copy(text: string) {
  try {
    await navigator.clipboard.writeText(text)
  } catch {
    // clipboard not available — do nothing
  }
}
</script>

<template>
  <Teleport to="body">
    <div
      class="fixed inset-0 z-[70] flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm"
      @click.self="emit('close')"
    >
      <div class="bg-white dark:bg-gray-900 rounded-xl shadow-2xl w-full max-w-md border border-gray-200 dark:border-gray-700 flex flex-col max-h-[80vh]">

        <!-- Header -->
        <div class="flex items-start justify-between px-5 py-3.5 border-b border-gray-100 dark:border-gray-800 shrink-0">
          <div>
            <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">
              {{ TYPE_LABELS[type] ?? type }}
            </h3>
            <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5 truncate max-w-xs">
              « {{ query }} »
            </p>
          </div>
          <button
            class="ml-3 shrink-0 p-1 rounded text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
            @click="emit('close')"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
          </button>
        </div>

        <!-- Loading -->
        <div v-if="loading" class="flex flex-col items-center justify-center gap-2 py-10">
          <svg class="w-6 h-6 animate-spin text-brand-500" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"/>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
          </svg>
          <p class="text-xs text-gray-400">Analyse en cours…</p>
        </div>

        <!-- Erreur -->
        <div v-else-if="error" class="px-5 py-6">
          <div class="flex items-start gap-3">
            <div class="shrink-0 w-8 h-8 rounded-full bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center mt-0.5">
              <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
              </svg>
            </div>
            <p class="text-sm text-gray-700 dark:text-gray-300">{{ error }}</p>
          </div>
        </div>

        <!-- Résultats -->
        <div v-else class="overflow-y-auto flex-1 divide-y divide-gray-100 dark:divide-gray-800">
          <div
            v-for="(item, i) in items"
            :key="i"
            class="flex items-start gap-3 px-5 py-3 group hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors"
          >
            <div class="flex-1 min-w-0">
              <p class="text-sm text-gray-900 dark:text-gray-100 leading-snug">{{ item.text }}</p>
              <p v-if="item.detail" class="text-xs text-gray-400 dark:text-gray-500 mt-0.5 italic">{{ item.detail }}</p>
            </div>
            <button
              class="shrink-0 p-1.5 rounded text-gray-300 dark:text-gray-600 hover:text-brand-500 dark:hover:text-brand-400 hover:bg-brand-50 dark:hover:bg-brand-900/20 transition-colors opacity-0 group-hover:opacity-100"
              title="Copier"
              @click="copy(item.text)"
            >
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
              </svg>
            </button>
          </div>
        </div>

        <!-- Footer -->
        <div class="px-5 py-3 border-t border-gray-100 dark:border-gray-800 shrink-0 flex justify-end">
          <button
            class="px-4 py-1.5 text-xs rounded-lg bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors font-medium"
            @click="emit('close')"
          >
            Fermer
          </button>
        </div>

      </div>
    </div>
  </Teleport>
</template>
