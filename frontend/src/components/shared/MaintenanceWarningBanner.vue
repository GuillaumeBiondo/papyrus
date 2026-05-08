<script setup lang="ts">
import { computed } from 'vue'
import { useMaintenanceStore } from '@/stores/maintenance.store'

const maintenance = useMaintenanceStore()

const startLabel = computed(() => {
  const startAt = maintenance.status?.start_at
  if (!startAt) return null
  return new Intl.DateTimeFormat('fr-FR', {
    dateStyle: 'long',
    timeStyle: 'short',
  }).format(new Date(startAt))
})
</script>

<template>
  <Transition name="slide-down">
    <div
      v-if="maintenance.showWarning"
      class="fixed top-0 inset-x-0 z-[9998] flex items-center justify-between gap-3
             bg-amber-50 dark:bg-amber-950/80 border-b border-amber-200 dark:border-amber-800
             px-4 py-2.5 text-sm"
    >
      <!-- Icône + message -->
      <div class="flex items-center gap-2 min-w-0">
        <svg class="w-4 h-4 text-amber-600 dark:text-amber-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
        </svg>
        <span class="text-amber-800 dark:text-amber-300 font-medium">Maintenance programmée</span>
        <span
          v-if="maintenance.status?.warning_message"
          class="text-amber-700 dark:text-amber-400 truncate"
        >— {{ maintenance.status.warning_message }}</span>
        <span v-else-if="startLabel" class="text-amber-700 dark:text-amber-400 truncate">
          — le {{ startLabel }}
        </span>
      </div>

      <!-- Bouton fermer -->
      <button
        class="shrink-0 text-amber-600 dark:text-amber-400 hover:text-amber-800 dark:hover:text-amber-200
               transition-colors rounded p-0.5"
        aria-label="Fermer"
        @click="maintenance.dismissWarning()"
      >
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>
    </div>
  </Transition>
</template>

<style scoped>
.slide-down-enter-active, .slide-down-leave-active { transition: transform 0.2s, opacity 0.2s; }
.slide-down-enter-from, .slide-down-leave-to { transform: translateY(-100%); opacity: 0; }
</style>
