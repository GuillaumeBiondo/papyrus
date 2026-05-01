import api from './api'
import type { Note } from '@/types'

export const notesService = {
  indexForProject: (projectId: string): Promise<{ data: Note[] }> =>
    api.get(`/projects/${projectId}/notes`).then((r) => r.data),

  storeForProject: (projectId: string, payload: { body: string }): Promise<Note> =>
    api.post(`/projects/${projectId}/notes`, payload).then((r) => r.data.data),

  indexForScene: (sceneId: string): Promise<{ data: Note[] }> =>
    api.get(`/scenes/${sceneId}/notes`).then((r) => r.data),

  indexForCard: (cardId: string): Promise<{ data: Note[] }> =>
    api.get(`/cards/${cardId}/notes`).then((r) => r.data),

  storeForScene: (sceneId: string, payload: { body: string }): Promise<Note> =>
    api.post(`/scenes/${sceneId}/notes`, payload).then((r) => r.data.data),

  storeForCard: (cardId: string, payload: { body: string }): Promise<Note> =>
    api.post(`/cards/${cardId}/notes`, payload).then((r) => r.data.data),

  update: (id: string, payload: { body: string }): Promise<Note> =>
    api.put(`/notes/${id}`, payload).then((r) => r.data.data),

  destroy: (id: string): Promise<void> =>
    api.delete(`/notes/${id}`),
}
