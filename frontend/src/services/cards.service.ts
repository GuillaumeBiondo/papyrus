import api from './api'
import type { Card, PaginatedResponse } from '@/types'

export const cardsService = {
  index: (
    projectId: string,
    params?: { type?: string; q?: string },
  ): Promise<PaginatedResponse<Card>> =>
    api.get(`/projects/${projectId}/cards`, { params }).then((r) => r.data),

  store: (projectId: string, payload: Partial<Card>): Promise<Card> =>
    api.post(`/projects/${projectId}/cards`, payload).then((r) => r.data.data),

  show: (id: string): Promise<Card> =>
    api.get(`/cards/${id}`).then((r) => r.data.data),

  update: (id: string, payload: Partial<Card>): Promise<Card> =>
    api.put(`/cards/${id}`, payload).then((r) => r.data.data),

  destroy: (id: string): Promise<void> =>
    api.delete(`/cards/${id}`),

  updateAttributes: (id: string, attributes: { key: string; value: unknown }[]): Promise<Card> =>
    api.put(`/cards/${id}/attributes`, { attributes }).then((r) => r.data.data),
}
