import api from './api'
import type { CardKeyword, KeywordOccurrence, PaginatedResponse } from '@/types'

export const keywordsService = {
  index: (cardId: string): Promise<CardKeyword[]> =>
    api.get(`/cards/${cardId}/keywords`).then((r) => r.data.data),

  store: (cardId: string, payload: Partial<CardKeyword>): Promise<CardKeyword> =>
    api.post(`/cards/${cardId}/keywords`, payload).then((r) => r.data.data),

  destroy: (cardId: string, keywordId: string): Promise<void> =>
    api.delete(`/cards/${cardId}/keywords/${keywordId}`),

  occurrences: (cardId: string): Promise<PaginatedResponse<KeywordOccurrence>> =>
    api.get(`/cards/${cardId}/occurrences`).then((r) => r.data),

  rebuildIndex: (projectId: string): Promise<void> =>
    api.post(`/projects/${projectId}/rebuild-index`),
}
