import api from './api'
import type { Chapter } from '@/types'

export const chaptersService = {
  index: (arcId: string): Promise<{ data: Chapter[] }> =>
    api.get(`/arcs/${arcId}/chapters`).then((r) => r.data),

  store: (arcId: string, payload: Partial<Chapter>): Promise<Chapter> =>
    api.post(`/arcs/${arcId}/chapters`, payload).then((r) => r.data.data),

  update: (id: string, payload: Partial<Chapter>): Promise<Chapter> =>
    api.put(`/chapters/${id}`, payload).then((r) => r.data.data),

  destroy: (id: string): Promise<void> =>
    api.delete(`/chapters/${id}`),

  reorder: (items: { id: string; order: number; arc_id?: string }[]): Promise<void> =>
    api.post('/chapters/reorder', { items }),
}
