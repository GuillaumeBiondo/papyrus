<script setup lang="ts">
import { ref } from 'vue'
import api from '@/services/api'

const emit = defineEmits<{ close: [] }>()

const subject = ref('')
const message = ref('')
const sending = ref(false)
const error   = ref('')
const success = ref(false)

async function send() {
  error.value = ''
  sending.value = true
  try {
    await api.post('/support', { subject: subject.value, message: message.value })
    success.value = true
    setTimeout(() => emit('close'), 1800)
  } catch (e: any) {
    error.value = e?.response?.data?.message ?? 'Erreur lors de l\'envoi.'
  } finally {
    sending.value = false
  }
}
</script>

<template>
  <Transition name="modal">
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40" @click.self="emit('close')">
      <div class="bg-white dark:bg-gray-900 rounded-xl shadow-xl w-full max-w-md p-6
                  border border-gray-200 dark:border-gray-800">
        <h2 class="text-base font-semibold text-gray-900 dark:text-gray-100 mb-4">
          Contacter le support
        </h2>

        <div v-if="success" class="py-6 text-center">
          <p class="text-green-600 dark:text-green-400 font-medium">Message envoyé ✓</p>
          <p class="text-sm text-gray-500 mt-1">Nous reviendrons vers toi rapidement.</p>
        </div>

        <form v-else class="space-y-4" @submit.prevent="send">
          <div>
            <label class="support-label">Objet</label>
            <input
              v-model="subject"
              type="text"
              required
              maxlength="150"
              class="support-input"
              placeholder="Ex : Problème de connexion…"
            />
          </div>
          <div>
            <label class="support-label">Message</label>
            <textarea
              v-model="message"
              required
              maxlength="5000"
              rows="6"
              class="support-input resize-none"
              placeholder="Décris ton problème en détail…"
            />
          </div>

          <p v-if="error" class="text-red-500 text-xs">{{ error }}</p>

          <div class="flex justify-end gap-2 pt-1">
            <button type="button" class="btn-ghost" @click="emit('close')">Annuler</button>
            <button type="submit" :disabled="sending" class="btn-primary">
              {{ sending ? 'Envoi…' : 'Envoyer' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </Transition>
</template>

<style scoped>
@reference "@/assets/main.css";
.support-label { @apply block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1; }
.support-input {
  @apply w-full rounded-md border border-gray-300 dark:border-gray-700
         bg-white dark:bg-gray-800 px-3 py-1.5 text-sm
         text-gray-900 dark:text-gray-100 outline-none
         focus:ring-2 focus:ring-brand-500 focus:border-brand-500;
}
.btn-ghost { @apply px-3 py-1.5 text-sm rounded-md text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800; }
.btn-primary { @apply px-3 py-1.5 text-sm rounded-md bg-brand-600 text-white hover:bg-brand-700 disabled:opacity-50 transition-colors; }
.modal-enter-active, .modal-leave-active { transition: opacity 0.15s; }
.modal-enter-from, .modal-leave-to { opacity: 0; }
</style>
