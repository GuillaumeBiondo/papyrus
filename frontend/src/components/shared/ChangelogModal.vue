<script setup lang="ts">
import { useChangelogStore } from '@/stores/changelog.store'
import MarkdownContent from './MarkdownContent.vue'

const changelog = useChangelogStore()

function formatDate(iso: string | null) {
  if (!iso) return ''
  return new Intl.DateTimeFormat('fr-FR', { dateStyle: 'long' }).format(new Date(iso))
}
</script>

<template>
  <Transition name="modal">
    <div
      v-if="changelog.open"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/40"
      @click.self="changelog.closeModal()"
    >
      <div class="bg-white dark:bg-gray-900 rounded-xl shadow-xl w-full max-w-xl
                  border border-gray-200 dark:border-gray-800 max-h-[80vh] flex flex-col">
        <!-- Header -->
        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 dark:border-gray-800">
          <h2 class="text-base font-semibold text-gray-900 dark:text-gray-100">Nouveautés</h2>
          <button
            v-if="changelog.unreadCount > 0"
            class="text-sm text-brand-600 dark:text-brand-400 hover:underline"
            @click="changelog.markAllRead()"
          >
            Tout marquer comme lu
          </button>
        </div>

        <!-- Body -->
        <div class="flex-1 overflow-y-auto divide-y divide-gray-100 dark:divide-gray-800">
          <div v-if="changelog.all.length === 0" class="py-10 text-center text-gray-400 text-sm">
            Aucune nouveauté pour le moment.
          </div>
          <div
            v-for="entry in changelog.all"
            :key="entry.id"
            class="px-5 py-4 transition-colors"
            :class="entry.read ? 'opacity-60' : 'bg-brand-50/40 dark:bg-brand-950/20'"
          >
            <div class="flex items-start justify-between gap-4 mb-2">
              <div class="flex items-center flex-wrap gap-1.5">
                <span
                  v-if="!entry.read"
                  class="w-2 h-2 rounded-full bg-red-500 shrink-0 mt-0.5"
                />
                <span v-if="entry.version" class="px-1.5 py-0.5 rounded bg-brand-100 dark:bg-brand-950 text-brand-700 dark:text-brand-300 text-xs font-mono">
                  v{{ entry.version }}
                </span>
                <span class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ entry.title }}</span>
                <span class="text-xs text-gray-400">{{ formatDate(entry.published_at) }}</span>
              </div>
              <button
                v-if="!entry.read"
                class="shrink-0 text-xs text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 mt-0.5"
                @click="changelog.markRead(entry.id)"
              >
                ✓ Lu
              </button>
            </div>
            <MarkdownContent :content="entry.body" class="text-sm text-gray-600 dark:text-gray-400" />
          </div>
        </div>

        <!-- Footer -->
        <div class="px-5 py-3 border-t border-gray-100 dark:border-gray-800 text-right">
          <button
            class="px-3 py-1.5 text-sm rounded-md bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors"
            @click="changelog.closeModal()"
          >
            Fermer
          </button>
        </div>
      </div>
    </div>
  </Transition>
</template>

<style scoped>
.modal-enter-active, .modal-leave-active { transition: opacity 0.15s; }
.modal-enter-from, .modal-leave-to { opacity: 0; }
</style>
