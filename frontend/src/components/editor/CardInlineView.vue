<script setup lang="ts">
import type { Card } from '@/types'

defineProps<{
  card: Card | undefined
  loading: boolean
  color: string
}>()
</script>

<template>
  <div class="mx-2 mb-2 rounded-lg border border-gray-100 dark:border-gray-700/60 bg-white dark:bg-gray-800/40 text-xs overflow-hidden">

    <!-- Chargement -->
    <div v-if="loading" class="py-4 text-center text-gray-400">Chargement…</div>

    <template v-else-if="card">

      <!-- Attributs -->
      <div v-if="card.attributes?.length" class="px-3 pt-2.5 pb-2 space-y-1.5">
        <div v-for="attr in card.attributes" :key="attr.id" class="flex gap-2">
          <span class="shrink-0 font-medium text-gray-500 dark:text-gray-400 w-24 truncate">{{ attr.key }}</span>
          <span class="text-gray-700 dark:text-gray-300 break-words min-w-0">{{ attr.value || '—' }}</span>
        </div>
      </div>

      <!-- Liaisons -->
      <div v-if="card.links?.length" class="px-3 py-2 border-t border-gray-100 dark:border-gray-700/60">
        <p class="text-gray-400 uppercase tracking-wide text-[10px] font-semibold mb-1.5">Liaisons</p>
        <div class="flex flex-wrap gap-1">
          <span
            v-for="link in card.links" :key="link.id"
            class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-md bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300"
          >
            {{ link.linked_card.title }}
            <span v-if="link.label" class="text-gray-400 dark:text-gray-500 italic">· {{ link.label }}</span>
          </span>
        </div>
      </div>

      <!-- Notes -->
      <div v-if="card.notes?.length" class="px-3 py-2 border-t border-gray-100 dark:border-gray-700/60 space-y-1.5">
        <p class="text-gray-400 uppercase tracking-wide text-[10px] font-semibold mb-1.5">Notes</p>
        <p v-for="note in card.notes" :key="note.id" class="text-gray-600 dark:text-gray-300 whitespace-pre-wrap leading-relaxed">{{ note.body }}</p>
      </div>

      <!-- Vide -->
      <div
        v-if="!card.attributes?.length && !card.links?.length && !card.notes?.length"
        class="py-3 text-center text-gray-400 italic"
      >Fiche vide</div>

    </template>
  </div>
</template>
