import api from './api'
import type { AdminStats, AdminUser, AiEnrichType, AiStats, AiVerification, AvailableFont, Changelog, ContentType, GenreAdmin, GenreCategoryAdmin, Setting } from '@/types'

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

  async updateMaintenanceBypass(userId: string, bypass: boolean): Promise<{ maintenance_bypass: boolean }> {
    const { data } = await api.put(`/admin/users/${userId}/maintenance-bypass`, { maintenance_bypass: bypass })
    return data
  },

  async updateBlockedStatus(userId: string, isBlocked: boolean, blockReason?: string | null): Promise<{ is_blocked: boolean; block_reason: string | null }> {
    const { data } = await api.put(`/admin/users/${userId}/block`, { is_blocked: isBlocked, block_reason: blockReason ?? null })
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

  async uploadChangelogImage(file: File): Promise<{ url: string }> {
    const form = new FormData()
    form.append('image', file)
    const { data } = await api.post('/admin/changelogs/images', form, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    return data
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

  // Fonts
  async getFonts(): Promise<{ fonts: AvailableFont[] }> {
    const { data } = await api.get('/admin/fonts')
    return data
  },

  async createFont(payload: Pick<AvailableFont, 'name' | 'google_font_slug' | 'css_family' | 'category'>): Promise<{ font: AvailableFont }> {
    const { data } = await api.post('/admin/fonts', payload)
    return data
  },

  async updateFont(id: number, payload: Partial<AvailableFont>): Promise<{ font: AvailableFont }> {
    const { data } = await api.put(`/admin/fonts/${id}`, payload)
    return data
  },

  async deleteFont(id: number): Promise<void> {
    await api.delete(`/admin/fonts/${id}`)
  },

  async reorderFonts(order: number[]): Promise<void> {
    await api.put('/admin/fonts/reorder', { order })
  },

  // AI Stats
  async getAiStats(): Promise<AiStats> {
    const { data } = await api.get('/admin/ai-stats')
    return data
  },

  // AI Verifications
  async getAiVerifications(): Promise<{ verifications: AiVerification[] }> {
    const { data } = await api.get('/admin/ai-verifications')
    return data
  },

  async createAiVerification(payload: Partial<AiVerification>): Promise<{ verification: AiVerification }> {
    const { data } = await api.post('/admin/ai-verifications', payload)
    return data
  },

  async updateAiVerification(id: number, payload: Partial<AiVerification>): Promise<{ verification: AiVerification }> {
    const { data } = await api.put(`/admin/ai-verifications/${id}`, payload)
    return data
  },

  async deleteAiVerification(id: number): Promise<void> {
    await api.delete(`/admin/ai-verifications/${id}`)
  },

  async reorderAiVerifications(order: number[]): Promise<void> {
    await api.put('/admin/ai-verifications/reorder', { order })
  },

  // AI Enrich Types
  async getAiEnrichTypes(): Promise<{ types: AiEnrichType[] }> {
    const { data } = await api.get('/admin/ai-enrich-types')
    return data
  },

  async createAiEnrichType(payload: Partial<AiEnrichType>): Promise<{ type: AiEnrichType }> {
    const { data } = await api.post('/admin/ai-enrich-types', payload)
    return data
  },

  async updateAiEnrichType(id: number, payload: Partial<AiEnrichType>): Promise<{ type: AiEnrichType }> {
    const { data } = await api.put(`/admin/ai-enrich-types/${id}`, payload)
    return data
  },

  async deleteAiEnrichType(id: number): Promise<void> {
    await api.delete(`/admin/ai-enrich-types/${id}`)
  },

  async reorderAiEnrichTypes(order: number[]): Promise<void> {
    await api.put('/admin/ai-enrich-types/reorder', { order })
  },

  async updatePremiumOverride(userId: string, premiumOverride: boolean): Promise<{ premium_override: boolean; effective_premium: boolean }> {
    const { data } = await api.put(`/admin/users/${userId}/premium-override`, { premium_override: premiumOverride })
    return data
  },

  async getWorkshops(): Promise<{ workshops: import('@/types').Workshop[] }> {
    const { data } = await api.get('/admin/workshops')
    return data
  },

  async updateWorkshop(id: number, payload: Partial<import('@/types').Workshop>): Promise<{ workshop: import('@/types').Workshop }> {
    const { data } = await api.put(`/admin/workshops/${id}`, payload)
    return data
  },

  // Genre categories
  async getGenreCategories(): Promise<{ categories: GenreCategoryAdmin[] }> {
    const { data } = await api.get('/admin/genre-categories')
    return data
  },

  async createGenreCategory(payload: Partial<GenreCategoryAdmin>): Promise<{ category: GenreCategoryAdmin }> {
    const { data } = await api.post('/admin/genre-categories', payload)
    return data
  },

  async updateGenreCategory(id: string, payload: Partial<GenreCategoryAdmin>): Promise<{ category: GenreCategoryAdmin }> {
    const { data } = await api.put(`/admin/genre-categories/${id}`, payload)
    return data
  },

  async deleteGenreCategory(id: string): Promise<void> {
    await api.delete(`/admin/genre-categories/${id}`)
  },

  async reorderGenreCategories(items: { id: string; sort_order: number }[]): Promise<void> {
    await api.put('/admin/genre-categories/reorder', { items })
  },

  // Genres
  async createGenre(categoryId: string, payload: Partial<GenreAdmin>): Promise<{ genre: GenreAdmin }> {
    const { data } = await api.post(`/admin/genre-categories/${categoryId}/genres`, payload)
    return data
  },

  async updateGenre(categoryId: string, genreId: string, payload: Partial<GenreAdmin>): Promise<{ genre: GenreAdmin }> {
    const { data } = await api.put(`/admin/genre-categories/${categoryId}/genres/${genreId}`, payload)
    return data
  },

  async deleteGenre(categoryId: string, genreId: string): Promise<void> {
    await api.delete(`/admin/genre-categories/${categoryId}/genres/${genreId}`)
  },

  async reorderGenres(categoryId: string, items: { id: string; sort_order: number }[]): Promise<void> {
    await api.put(`/admin/genre-categories/${categoryId}/genres/reorder`, { items })
  },

  // Proximity matrix
  async getGenreProximity(): Promise<{ proximity: Record<string, Record<string, number>> }> {
    const { data } = await api.get('/admin/genre-proximity')
    return data
  },

  async updateGenreProximity(proximity: Record<string, Record<string, number>>): Promise<void> {
    await api.put('/admin/genre-proximity', { proximity })
  },
}
