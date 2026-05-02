import api from './api'
import type { PaginatedResponse, Project } from '@/types'

export const projectsService = {
  index: (): Promise<PaginatedResponse<Project>> =>
    api.get('/projects').then((r) => r.data),

  store: (payload: Partial<Project>): Promise<Project> =>
    api.post('/projects', payload).then((r) => r.data.data),

  show: (id: string): Promise<Project> =>
    api.get(`/projects/${id}`).then((r) => r.data.data),

  update: (id: string, payload: Partial<Project>): Promise<Project> =>
    api.put(`/projects/${id}`, payload).then((r) => r.data.data),

  destroy: (id: string): Promise<void> =>
    api.delete(`/projects/${id}`),

  export: (id: string, format: 'txt' | 'md' | 'zip'): Promise<Blob> =>
    api.get(`/projects/${id}/export/${format}`, { responseType: 'blob' }).then(r => r.data),
}
