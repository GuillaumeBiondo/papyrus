import api from './api'
import type { EditionDocumentEntry, EditionSettings } from '@/types'

export const editionService = {
  // ── Documents ──────────────────────────────────────────────────
  listDocuments: (projectId: string): Promise<{ data: EditionDocumentEntry[] }> =>
    api.get(`/projects/${projectId}/edition/documents`).then((r) => r.data),

  toggleDocument: (
    projectId: string,
    payload: { type: string; is_enabled: boolean },
  ): Promise<{ data: Partial<EditionDocumentEntry> }> =>
    api.post(`/projects/${projectId}/edition/documents/toggle`, payload).then((r) => r.data),

  getDocument: (id: number): Promise<{ data: { id: number; type: string; title: string | null; content: string | null; is_enabled: boolean; updated_at: string } }> =>
    api.get(`/edition/documents/${id}`).then((r) => r.data),

  updateDocument: (
    id: number,
    payload: { title?: string | null; content?: string | null; sort_order?: number },
  ): Promise<{ data: Partial<EditionDocumentEntry> }> =>
    api.put(`/edition/documents/${id}`, payload).then((r) => r.data),

  // ── Réglages ───────────────────────────────────────────────────
  getSettings: (projectId: string): Promise<{ data: EditionSettings }> =>
    api.get(`/projects/${projectId}/edition/settings`).then((r) => r.data),

  updateSettings: (
    projectId: string,
    settings: Partial<EditionSettings>,
  ): Promise<{ data: EditionSettings }> =>
    api.put(`/projects/${projectId}/edition/settings`, { settings }).then((r) => r.data),
}
