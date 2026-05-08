<script setup lang="ts">
import { computed } from 'vue'
import { useMaintenanceStore } from '@/stores/maintenance.store'
import { useAuthStore } from '@/stores/auth.store'

const maintenance = useMaintenanceStore()
const auth = useAuthStore()

const endLabel = computed(() => {
  const endAt = maintenance.status?.end_at
  if (!endAt) return null
  return new Intl.DateTimeFormat('fr-FR', {
    dateStyle: 'long',
    timeStyle: 'short',
  }).format(new Date(endAt))
})
</script>

<template>
  <Transition name="fade">
    <div
      v-if="maintenance.isBlocked"
      class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/60 backdrop-blur-sm"
    >
      <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-2xl border border-gray-200 dark:border-gray-800 w-full max-w-md mx-4 p-8 text-center">
        <!-- Icône -->
        <div class="mx-auto mb-5 w-14 h-14 rounded-full bg-amber-100 dark:bg-amber-950 flex items-center justify-center">
          <svg class="w-7 h-7 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17L17.25 21A2.652 2.652 0 0021 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 11-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 004.486-6.336l-3.276 3.277a3.004 3.004 0 01-2.25-2.25l3.276-3.276a4.5 4.5 0 00-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26m-1.745 1.437l1.745-1.437m6.615 8.206L15.75 15.75M4.867 19.125h.008v.008h-.008v-.008z" />
          </svg>
        </div>

        <!-- Titre -->
        <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">
          Maintenance en cours
        </h2>

        <!-- Message -->
        <p class="text-sm text-gray-600 dark:text-gray-400 mb-4 leading-relaxed">
          {{ maintenance.status?.message }}
        </p>

        <!-- Date de fin si disponible -->
        <p v-if="endLabel" class="text-xs text-gray-400 dark:text-gray-500 mb-6">
          Reprise prévue le {{ endLabel }}
        </p>

        <!-- Bouton déconnexion -->
        <button
          class="mt-2 px-4 py-2 text-sm rounded-lg text-gray-600 dark:text-gray-400
                 border border-gray-300 dark:border-gray-700
                 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors"
          @click="auth.logout()"
        >
          Se déconnecter
        </button>
      </div>
    </div>
  </Transition>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
