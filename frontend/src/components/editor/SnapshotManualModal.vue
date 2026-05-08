<script setup lang="ts">
import { ref } from 'vue'

const emit = defineEmits<{
  confirm: [label: string]
  cancel:  []
}>()

const label   = ref('')
const inputEl = ref<HTMLInputElement | null>(null)

function onConfirm() {
  emit('confirm', label.value.trim() || '')
  label.value = ''
}

function onCancel() {
  label.value = ''
  emit('cancel')
}
</script>

<template>
  <Teleport to="body">
    <div
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm"
      @click.self="onCancel"
    >
      <div class="w-80 bg-white dark:bg-gray-900 rounded-xl shadow-xl border border-gray-200 dark:border-gray-700 p-5">
        <div class="flex items-center gap-2 mb-4">
          <span class="text-xl">📷</span>
          <h2 class="text-sm font-semibold text-gray-800 dark:text-gray-100">Snapshot manuel</h2>
        </div>

        <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">
          Un nom facultatif pour identifier ce moment de l'écriture.
        </p>

        <input
          ref="inputEl"
          v-model="label"
          type="text"
          placeholder="ex : Fin du chapitre 3, Avant la réécriture…"
          maxlength="200"
          class="w-full text-sm rounded-lg border border-gray-300 dark:border-gray-600
                 bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200
                 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-500 mb-4"
          @keyup.enter="onConfirm"
          @keyup.escape="onCancel"
        />

        <div class="flex gap-2 justify-end">
          <button
            class="px-3 py-1.5 text-sm text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 transition-colors"
            @click="onCancel"
          >Annuler</button>
          <button
            class="px-4 py-1.5 text-sm bg-brand-600 hover:bg-brand-700 text-white rounded-lg transition-colors"
            @click="onConfirm"
          >Sauvegarder</button>
        </div>
      </div>
    </div>
  </Teleport>
</template>
