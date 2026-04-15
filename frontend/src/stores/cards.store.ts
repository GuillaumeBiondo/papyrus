import { defineStore } from 'pinia'
import { ref } from 'vue'
import { cardsService } from '@/services/cards.service'
import { keywordsService } from '@/services/keywords.service'
import type { Card, KeywordOccurrence } from '@/types'

export const useCardsStore = defineStore('cards', () => {
  const cards = ref<Card[]>([])
  const activeCard = ref<Card | null>(null)
  const occurrences = ref<KeywordOccurrence[]>([])
  const rebuildStatus = ref<'idle' | 'pending' | 'done'>('idle')

  async function fetchForProject(projectId: string) {
    const res = await cardsService.index(projectId)
    cards.value = res.data
  }

  async function rebuildIndex(projectId: string) {
    rebuildStatus.value = 'pending'
    try {
      await keywordsService.rebuildIndex(projectId)
      rebuildStatus.value = 'done'
      if (activeCard.value) {
        const res = await keywordsService.occurrences(activeCard.value.id)
        occurrences.value = res.data
      }
    } catch {
      rebuildStatus.value = 'idle'
    }
  }

  return { cards, activeCard, occurrences, rebuildStatus, fetchForProject, rebuildIndex }
})
