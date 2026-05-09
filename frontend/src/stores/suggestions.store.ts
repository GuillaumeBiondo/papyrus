import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import type { ProposeSuggestionsOptions, SuggestionBatch, SuggestionChange } from '@/types/suggestion.types'

export const useSuggestionsStore = defineStore('suggestions', () => {
  const batches = ref<SuggestionBatch[]>([])
  const focusedBatchIndex = ref(0)

  const pendingBatches = computed(() => batches.value.filter(b => b.status === 'pending'))
  const hasPending = computed(() => pendingBatches.value.length > 0)
  const focusedBatch = computed(() => pendingBatches.value[focusedBatchIndex.value] ?? null)

  function addBatch(changes: SuggestionChange[], options: ProposeSuggestionsOptions = {}): SuggestionBatch {
    const batch: SuggestionBatch = {
      id: crypto.randomUUID(),
      label: options.label ?? 'Suggestion de modification',
      source: options.source ?? 'manual',
      changes: [...changes],
      status: 'pending',
      createdAt: new Date(),
    }
    batches.value.push(batch)
    focusedBatchIndex.value = pendingBatches.value.length - 1
    return batch
  }

  function markAccepted(batchId: string) {
    const batch = batches.value.find(b => b.id === batchId)
    if (batch) batch.status = 'accepted'
    clampFocusedIndex()
  }

  function markRejected(batchId: string) {
    const batch = batches.value.find(b => b.id === batchId)
    if (batch) batch.status = 'rejected'
    clampFocusedIndex()
  }

  function removeChange(batchId: string, changeIdx: number) {
    const batch = batches.value.find(b => b.id === batchId)
    if (!batch) return
    batch.changes.splice(changeIdx, 1)
    if (batch.changes.length === 0) {
      batch.status = 'rejected'
      clampFocusedIndex()
    }
  }

  function navigatePrev() {
    if (focusedBatchIndex.value > 0) focusedBatchIndex.value--
  }

  function navigateNext() {
    const max = pendingBatches.value.length - 1
    if (focusedBatchIndex.value < max) focusedBatchIndex.value++
  }

  function clearAll() {
    batches.value = []
    focusedBatchIndex.value = 0
  }

  function clampFocusedIndex() {
    const max = pendingBatches.value.length - 1
    if (focusedBatchIndex.value > max) focusedBatchIndex.value = Math.max(0, max)
  }

  return {
    batches,
    focusedBatchIndex,
    pendingBatches,
    hasPending,
    focusedBatch,
    addBatch,
    markAccepted,
    markRejected,
    removeChange,
    navigatePrev,
    navigateNext,
    clearAll,
  }
})
