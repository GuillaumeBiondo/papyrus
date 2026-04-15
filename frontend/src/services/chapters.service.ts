import api from './api'
import type { Chapter, PaginatedResponse } from '@/types'

export const chaptersService = {
  index: (projectId: string): Promise<PaginatedResponse<Chapter>> =>
    api.get(`/projects/${projectId}/chapters`).then((r) => r.data),

  store: (projectId: string, payload: Partial<Chapter>): Promise<Chapter> =>
    api.post(`/projects/${projectId}/chapters`, payload).then((r) => r.data.data),

  update: (id: string, payload: Partial<Chapter>): Promise<Chapter> =>
    api.put(`/chapters/${id}`, payload).then((r) => r.data.data),

  destroy: (id: string): Promise<void> =>
    api.delete(`/chapters/${id}`),

  reorder: (items: { id: string; order: number }[]): Promise<void> =>
    api.post('/chapters/reorder', { items }),
}
