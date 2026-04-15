<script setup lang="ts">
import { onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { useCardsStore } from '@/stores/cards.store'

const route = useRoute()
const cards = useCardsStore()

onMounted(() => {
  cards.fetchForProject(route.params.projectId as string)
})
</script>

<template>
  <div class="p-6 max-w-5xl mx-auto">
    <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-6">Fiches</h1>

    <div v-if="!cards.cards.length" class="text-sm text-gray-400 text-center py-16">
      Aucune fiche. Crée des personnages, lieux, événements…
    </div>

    <div v-else class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3">
      <div
        v-for="card in cards.cards"
        :key="card.id"
        class="border border-gray-200 dark:border-gray-700 rounded-lg p-3
               bg-white dark:bg-gray-900 cursor-pointer hover:shadow-sm transition-shadow"
        @click="cards.activeCard = card"
      >
        <span class="text-xs text-gray-400 capitalize">{{ card.type }}</span>
        <p class="text-sm font-medium text-gray-900 dark:text-gray-100 mt-0.5 truncate">
          {{ card.title }}
        </p>
      </div>
    </div>
  </div>
</template>
