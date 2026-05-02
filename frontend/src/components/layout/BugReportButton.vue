<script setup lang="ts">
import { ref } from 'vue'
import { bugReportService } from '@/services/bugReport.service'
import { getBugSnapshot } from '@/composables/bugBuffer'

const open       = ref(false)
const message    = ref('')
const currentUrl = ref('')
const status     = ref<'idle' | 'sending' | 'sent' | 'error'>('idle')

function openModal() {
  message.value    = ''
  currentUrl.value = window.location.href
  status.value     = 'idle'
  open.value       = true
}

function close() {
  open.value = false
}

async function submit() {
  if (!message.value.trim()) return
  status.value = 'sending'
  const { consoleErrors, apiCalls } = getBugSnapshot()
  try {
    await bugReportService.send({
      message:        message.value.trim(),
      url:            window.location.href,
      api_calls:      apiCalls,
      console_errors: consoleErrors,
    })
    status.value = 'sent'
    setTimeout(close, 1800)
  } catch {
    status.value = 'error'
  }
}
</script>

<template>
  <button
    class="btn-ghost text-gray-500 dark:text-gray-400 hover:text-red-500 dark:hover:text-red-400"
    title="Signaler un bug"
    @click="openModal"
  >
    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71
               3.86a2 2 0 00-3.42 0z" />
    </svg>
  </button>

  <Teleport to="body">
    <Transition name="fade">
      <div
        v-if="open"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
        @click.self="close"
      >
        <div class="w-full max-w-md bg-white dark:bg-gray-900 rounded-xl shadow-xl p-6 flex flex-col gap-4">

          <div class="flex items-center justify-between">
            <h2 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Signaler un bug</h2>
            <button class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 text-lg leading-none" @click="close">✕</button>
          </div>

          <div class="text-xs text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-800 rounded px-3 py-1.5 truncate">
            {{ currentUrl }}
          </div>

          <textarea
            v-model="message"
            rows="5"
            placeholder="Décris le problème rencontré…"
            class="w-full text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-500 resize-none"
            :disabled="status === 'sending' || status === 'sent'"
          />

          <Transition name="fade" mode="out-in">
            <p v-if="status === 'sent'" key="sent" class="text-xs text-green-600 dark:text-green-400 font-medium text-center">
              Rapport envoyé, merci !
            </p>
            <p v-else-if="status === 'error'" key="err" class="text-xs text-red-500 text-center">
              Erreur lors de l'envoi. Réessaie dans un instant.
            </p>
            <div v-else key="actions" class="flex justify-end gap-2">
              <button
                class="px-3 py-1.5 text-sm text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 transition-colors"
                @click="close"
              >Annuler</button>
              <button
                class="px-4 py-1.5 text-sm font-medium rounded-lg bg-brand-600 text-white hover:bg-brand-700 disabled:opacity-50 transition-colors"
                :disabled="!message.trim() || status === 'sending'"
                @click="submit"
              >
                {{ status === 'sending' ? 'Envoi…' : 'Envoyer' }}
              </button>
            </div>
          </Transition>

        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
