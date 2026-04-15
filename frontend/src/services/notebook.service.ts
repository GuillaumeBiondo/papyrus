import api from './api'
import type { NotebookEntry, PaginatedResponse } from '@/types'

export const notebookService = {
  index: (params?: { project_id?: string; free?: boolean }): Promise<PaginatedResponse<NotebookEntry>> =>
    api.get('/notebook', { params }).then((r) => r.data),

  store: (payload: Partial<NotebookEntry>): Promise<NotebookEntry> =>
    api.post('/notebook', payload).then((r) => r.data.data),

  show: (id: string): Promise<NotebookEntry> =>
    api.get(`/notebook/${id}`).then((r) => r.data.data),

  update: (id: string, payload: Partial<NotebookEntry>): Promise<NotebookEntry> =>
    api.put(`/notebook/${id}`, payload).then((r) => r.data.data),

  destroy: (id: string): Promise<void> =>
    api.delete(`/notebook/${id}`),
}
