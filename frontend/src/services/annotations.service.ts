import api from './api'
import type { Annotation, PaginatedResponse } from '@/types'

export const annotationsService = {
  index: (sceneId: string): Promise<PaginatedResponse<Annotation>> =>
    api.get(`/scenes/${sceneId}/annotations`).then((r) => r.data),

  store: (sceneId: string, payload: Partial<Annotation>): Promise<Annotation> =>
    api.post(`/scenes/${sceneId}/annotations`, payload).then((r) => r.data.data),

  update: (id: string, payload: Partial<Annotation>): Promise<Annotation> =>
    api.put(`/annotations/${id}`, payload).then((r) => r.data.data),

  destroy: (id: string): Promise<void> =>
    api.delete(`/annotations/${id}`),

  linkCard: (annotationId: string, cardId: string): Promise<void> =>
    api.post(`/annotations/${annotationId}/cards/${cardId}`),

  unlinkCard: (annotationId: string, cardId: string): Promise<void> =>
    api.delete(`/annotations/${annotationId}/cards/${cardId}`),
}
