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

  saveSummary: (id: string, summary: string | null): Promise<{ summary: string | null }> =>
    api.put(`/arcs/${id}/summary`, { summary }).then((r) => r.data),

  generateSummary: (id: string): Promise<{ summary: string; summary_generated_at: string | null }> =>
    api.post(`/arcs/${id}/summary/generate`).then((r) => r.data),
}
