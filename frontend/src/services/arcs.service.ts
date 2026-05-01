import api from './api'
import type { Arc } from '@/types'

export const arcsService = {
  index: (projectId: string): Promise<{ data: Arc[] }> =>
    api.get(`/projects/${projectId}/arcs`).then((r) => r.data),

  store: (projectId: string, payload: Partial<Arc>): Promise<Arc> =>
    api.post(`/projects/${projectId}/arcs`, payload).then((r) => r.data.data),

  update: (id: string, payload: Partial<Arc>): Promise<Arc> =>
    api.put(`/arcs/${id}`, payload).then((r) => r.data.data),

  destroy: (id: string): Promise<void> =>
    api.delete(`/arcs/${id}`),

  reorder: (projectId: string, items: { id: string; order: number }[]): Promise<void> =>
    api.post(`/projects/${projectId}/arcs/reorder`, { items }),
}
