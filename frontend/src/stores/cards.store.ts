import { defineStore } from 'pinia'
import { ref } from 'vue'
import { cardsService } from '@/services/cards.service'
import { keywordsService } from '@/services/keywords.service'
import type { Card, CardKeyword, CardLink, KeywordOccurrence } from '@/types'

export const useCardsStore = defineStore('cards', () => {
  const cards = ref<Card[]>([])
  const activeCard = ref<Card | null>(null)
  const occurrences = ref<KeywordOccurrence[]>([])
  const rebuildStatus = ref<'idle' | 'pending' | 'done'>('idle')
  const loading = ref(false)
  const showKeywordForm = ref(false)

  async function fetchForProject(projectId: string) {
    loading.value = true
    try {
      const res = await cardsService.index(projectId)
      cards.value = res.data
    } finally {
      loading.value = false
    }
  }

  async function loadCard(id: string) {
    const card = await cardsService.show(id)
    activeCard.value = card
    occurrences.value = []
    return card
  }

  async function createCard(projectId: string, payload: Partial<Card>): Promise<Card> {
    const card = await cardsService.store(projectId, payload)
    cards.value.unshift(card)
    activeCard.value = card
    return card
  }

  async function updateCard(payload: Partial<Card>) {
    if (!activeCard.value) return
    const updated = await cardsService.update(activeCard.value.id, payload)
    activeCard.value = updated
    const idx = cards.value.findIndex(c => c.id === updated.id)
    if (idx !== -1) cards.value[idx] = updated
  }

  async function updateAttributes(cardId: string, attrs: { key: string; value: unknown }[]) {
    const updated = await cardsService.updateAttributes(cardId, attrs)
    if (activeCard.value?.id === cardId) activeCard.value = updated
    return updated
  }

  async function addKeyword(keyword: string, caseSensitive = false): Promise<CardKeyword> {
    if (!activeCard.value) throw new Error('No active card')
    const kw = await keywordsService.store(activeCard.value.id, { keyword, case_sensitive: caseSensitive })
    if (!activeCard.value.keywords) activeCard.value.keywords = []
    activeCard.value.keywords.push(kw)
    showKeywordForm.value = false
    return kw
  }

  async function removeKeyword(keywordId: string) {
    if (!activeCard.value) return
    await keywordsService.destroy(activeCard.value.id, keywordId)
    if (activeCard.value.keywords) {
      activeCard.value.keywords = activeCard.value.keywords.filter(k => k.id !== keywordId)
    }
  }

  function openKeywordForm() {
    showKeywordForm.value = true
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

  async function loadOccurrences() {
    if (!activeCard.value) return
    const res = await keywordsService.occurrences(activeCard.value.id)
    occurrences.value = res.data
  }

  async function addLink(linkedCardId: string, label: string | null = null): Promise<CardLink> {
    if (!activeCard.value) throw new Error('No active card')
    const link = await cardsService.storeLink(activeCard.value.id, {
      linked_card_id: linkedCardId,
      ...(label ? { label } : {}),
    })
    if (!activeCard.value.links) activeCard.value.links = []
    activeCard.value.links.push(link)
    return link
  }

  async function removeLink(linkId: string) {
    if (!activeCard.value) return
    await cardsService.destroyLink(activeCard.value.id, linkId)
    if (activeCard.value.links) {
      activeCard.value.links = activeCard.value.links.filter((l) => l.id !== linkId)
    }
  }

  return {
    cards, activeCard, occurrences, rebuildStatus, loading, showKeywordForm,
    fetchForProject, loadCard, createCard, updateCard, updateAttributes,
    addKeyword, removeKeyword, openKeywordForm,
    rebuildIndex, loadOccurrences,
    addLink, removeLink,
  }
})
