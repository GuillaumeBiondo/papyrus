import api from './api'
import type { PaginatedResponse, Scene } from '@/types'

export const scenesService = {
  index: (chapterId: string): Promise<PaginatedResponse<Scene>> =>
    api.get(`/chapters/${chapterId}/scenes`).then((r) => r.data),

  store: (chapterId: string, payload: Partial<Scene>): Promise<Scene> =>
    api.post(`/chapters/${chapterId}/scenes`, payload).then((r) => r.data.data),

  show: (id: string): Promise<Scene> =>
    api.get(`/scenes/${id}`).then((r) => r.data.data),

  update: (id: string, payload: Partial<Scene>): Promise<Scene> =>
    api.put(`/scenes/${id}`, payload).then((r) => r.data.data),

  destroy: (id: string): Promise<void> =>
    api.delete(`/scenes/${id}`),

  reorder: (items: { id: string; order: number; chapter_id?: string }[]): Promise<void> =>
    api.post('/scenes/reorder', { items }),
}
