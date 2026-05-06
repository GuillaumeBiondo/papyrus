import api from './api'
import type { AdminStats, AdminUser, Changelog, ContentType, Setting } from '@/types'

export const adminService = {
  // Dashboard
  async getDashboard(): Promise<{ stats: AdminStats; recent_users: AdminUser[] }> {
    const { data } = await api.get('/admin/dashboard')
    return data
  },

  // Users
  async getUsers(): Promise<{ users: AdminUser[] }> {
    const { data } = await api.get('/admin/users')
    return data
  },

  async createUser(payload: { name: string; email: string; password: string }): Promise<{ user: AdminUser }> {
    const { data } = await api.post('/admin/users', payload)
    return data
  },

  // Content types
  async getContentTypes(): Promise<{ content_types: ContentType[] }> {
    const { data } = await api.get('/admin/content-types')
    return data
  },

  async createContentType(payload: Partial<ContentType>): Promise<{ content_type: ContentType }> {
    const { data } = await api.post('/admin/content-types', payload)
    return data
  },

  async updateContentType(id: string, payload: Partial<ContentType>): Promise<{ content_type: ContentType }> {
    const { data } = await api.put(`/admin/content-types/${id}`, payload)
    return data
  },

  // Changelogs
  async getChangelogs(): Promise<{ changelogs: Changelog[] }> {
    const { data } = await api.get('/admin/changelogs')
    return data
  },

  async createChangelog(payload: Partial<Changelog>): Promise<{ changelog: Changelog }> {
    const { data } = await api.post('/admin/changelogs', payload)
    return data
  },

  async updateChangelog(id: string, payload: Partial<Changelog>): Promise<{ changelog: Changelog }> {
    const { data } = await api.put(`/admin/changelogs/${id}`, payload)
    return data
  },

  async deleteChangelog(id: string): Promise<void> {
    await api.delete(`/admin/changelogs/${id}`)
  },

  // Settings
  async getSettings(): Promise<{ settings: Setting[] }> {
    const { data } = await api.get('/admin/settings')
    return data
  },

  async updateSetting(key: string, value: unknown): Promise<{ setting: Setting }> {
    const { data } = await api.put(`/admin/settings/${key}`, { value })
    return data
  },
}
