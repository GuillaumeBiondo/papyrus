<script setup lang="ts">
import type { Card } from '@/types'

defineProps<{
  card: Card | undefined
  loading: boolean
  color: string
}>()
</script>

<template>
  <div class="text-xs border-t border-gray-100 dark:border-gray-700/50 overflow-hidden">

    <!-- Chargement -->
    <div v-if="loading" class="py-4 text-center text-gray-400">Chargement…</div>

    <template v-else-if="card">

      <!-- Attributs -->
      <div v-if="card.attributes?.length" class="px-3 pt-3 pb-2.5 space-y-2">
        <div v-for="attr in card.attributes" :key="attr.id" class="flex gap-3">
          <span class="shrink-0 text-gray-400 dark:text-gray-500 w-20 truncate">{{ attr.key }}</span>
          <span class="text-gray-700 dark:text-gray-200 break-words min-w-0 font-medium">{{ attr.value || '—' }}</span>
        </div>
      </div>

      <!-- Lore -->
      <div v-if="card.lore" class="px-3 py-2.5 border-t border-gray-100 dark:border-gray-700/50">
        <p class="text-[10px] font-semibold uppercase tracking-wider text-gray-400 mb-2">Lore</p>
        <p class="text-gray-600 dark:text-gray-300 whitespace-pre-wrap leading-relaxed">{{ card.lore }}</p>
      </div>

      <!-- Liaisons -->
      <div v-if="card.links?.length" class="px-3 py-2.5 border-t border-gray-100 dark:border-gray-700/50">
        <p class="text-[10px] font-semibold uppercase tracking-wider text-gray-400 mb-2">Liaisons</p>
        <div class="space-y-1.5">
          <div v-for="link in card.links" :key="link.id">
            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-gray-100 dark:bg-gray-700/60 text-gray-600 dark:text-gray-300 text-[11px]">
              {{ link.linked_card.title }}
              <span v-if="link.label" class="text-gray-400 italic">· {{ link.label }}</span>
            </span>
            <p v-if="link.description" class="mt-0.5 pl-2 text-gray-500 dark:text-gray-400 italic leading-relaxed">{{ link.description }}</p>
          </div>
        </div>
      </div>

      <!-- Notes -->
      <div v-if="card.notes?.length" class="px-3 py-2.5 border-t border-gray-100 dark:border-gray-700/50 space-y-1.5">
        <p class="text-[10px] font-semibold uppercase tracking-wider text-gray-400 mb-2">Notes</p>
        <p v-for="note in card.notes" :key="note.id" class="text-gray-600 dark:text-gray-300 whitespace-pre-wrap leading-relaxed">{{ note.body }}</p>
      </div>

      <!-- Vide -->
      <div
        v-if="!card.attributes?.length && !card.lore && !card.links?.length && !card.notes?.length"
        class="py-4 text-center text-gray-400 italic"
      >Fiche vide</div>

    </template>
  </div>
</template>
