import api from './api'
import type { EditionPreset, EditionSettings } from '@/types'

export const editionPresetsService = {
  list: () =>
    api.get<EditionPreset[]>('/edition/presets'),

  save: (name: string, settings: EditionSettings) =>
    api.post<EditionPreset>('/edition/presets', { name, settings }),

  update: (id: number, payload: { name?: string; settings?: EditionSettings }) =>
    api.put<EditionPreset>(`/edition/presets/${id}`, payload),

  remove: (id: number) =>
    api.delete(`/edition/presets/${id}`),
}
