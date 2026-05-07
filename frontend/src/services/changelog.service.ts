import api from './api'
import type { Changelog } from '@/types'

export const changelogService = {
  async getAll(): Promise<{ changelogs: (Changelog & { read: boolean })[] }> {
    const { data } = await api.get('/v1/changelogs')
    return data
  },

  async getUnread(): Promise<{ changelogs: Changelog[]; count: number }> {
    const { data } = await api.get('/v1/changelogs/unread')
    return data
  },

  async markRead(id: string): Promise<void> {
    await api.post(`/v1/changelogs/${id}/read`)
  },

  async markAllRead(): Promise<void> {
    await api.post('/v1/changelogs/mark-all-read')
  },
}
