import api from './api'
import type { Card, CardImage, CardLink, PaginatedResponse } from '@/types'

export const cardsService = {
  index: (
    projectId: string,
    params?: { type?: string; q?: string; per_page?: number },
  ): Promise<PaginatedResponse<Card>> =>
    api.get(`/projects/${projectId}/cards`, { params }).then((r) => r.data),

  byKeywordsInScene: (sceneId: string): Promise<{ data: Card[] }> =>
    api.get(`/scenes/${sceneId}/cards-by-keywords`).then((r) => r.data),

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

  storeLink: (cardId: string, payload: { linked_card_id: string; label?: string; description?: string }): Promise<CardLink> =>
    api.post(`/cards/${cardId}/links`, payload).then((r) => r.data.data),

  updateLink: (cardId: string, linkId: string, payload: { label?: string | null; description?: string | null }): Promise<CardLink> =>
    api.put(`/cards/${cardId}/links/${linkId}`, payload).then((r) => r.data.data),

  destroyLink: (cardId: string, linkId: string): Promise<void> =>
    api.delete(`/cards/${cardId}/links/${linkId}`),

  integrateLoreNote: (cardId: string, noteId: string): Promise<{ lore: string }> =>
    api.post(`/cards/${cardId}/integrate-note`, { note_id: noteId }).then((r) => r.data),

  indexImages: (cardId: string): Promise<{ data: CardImage[] }> =>
    api.get(`/cards/${cardId}/images`).then((r) => r.data),

  uploadImage: (cardId: string, file: File): Promise<CardImage> => {
    const form = new FormData()
    form.append('image', file)
    return api.post(`/cards/${cardId}/images`, form, {
      headers: { 'Content-Type': 'multipart/form-data' },
    }).then((r) => r.data.data)
  },

  destroyImage: (cardId: string, imageId: string): Promise<void> =>
    api.delete(`/cards/${cardId}/images/${imageId}`),

  setAvatarImage: (cardId: string, imageId: string): Promise<CardImage> =>
    api.put(`/cards/${cardId}/images/${imageId}/avatar`).then((r) => r.data.data),
}
