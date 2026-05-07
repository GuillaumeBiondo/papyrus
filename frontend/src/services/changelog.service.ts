import api from './api'
import type { Changelog } from '@/types'

export const changelogService = {
  async getAll(): Promise<{ changelogs: (Changelog & { read: boolean })[] }> {
    const { data } = await api.get('/changelogs')
    return data
  },

  async getUnread(): Promise<{ changelogs: Changelog[]; count: number }> {
    const { data } = await api.get('/changelogs/unread')
    return data
  },

  async markRead(id: string): Promise<void> {
    await api.post(`/changelogs/${id}/read`)
  },

  async markAllRead(): Promise<void> {
    await api.post('/changelogs/mark-all-read')
  },
}
