import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import { authService } from '@/services/auth.service'
import { useMaintenanceStore } from '@/stores/maintenance.store'
import type { User, UserPreferences } from '@/types'

export const useAuthStore = defineStore('auth', () => {
  const user = ref<User | null>(null)

  const isAuthenticated = computed(() => !!user.value)
  const isBetaReader = computed(() => user.value?.currentRole === 'beta_reader')
  const isAdmin = computed(() => user.value?.role === 'admin')
  const preferences = computed(() => user.value?.preferences ?? {} as Partial<UserPreferences>)

  async function login(email: string, password: string) {
    await authService.csrfCookie()
    const data = await authService.login(email, password)
    user.value = data.user
    useMaintenanceStore().fetch()
  }

  async function logout() {
    await authService.logout()
    user.value = null
  }

  async function tryRestoreSession() {
    try {
      user.value = await authService.me()
      useMaintenanceStore().fetch()
    } catch {
      user.value = null
    }
  }

  async function updateProfile(payload: { name?: string; bio?: string | null }) {
    const { user: updated } = await authService.updateProfile(payload)
    if (user.value) user.value = { ...user.value, ...updated }
  }

  async function uploadAvatar(file: File) {
    const { user: updated } = await authService.uploadAvatar(file)
    if (user.value) user.value = { ...user.value, ...updated }
  }

  async function destroyAvatar() {
    const { user: updated } = await authService.destroyAvatar()
    if (user.value) user.value = { ...user.value, ...updated }
  }

  async function updatePreferences(patch: Partial<UserPreferences>) {
    const { user: updated } = await authService.updatePreferences(patch as Record<string, unknown>)
    if (user.value) user.value = { ...user.value, ...updated }
  }

  return {
    user, isAuthenticated, isBetaReader, isAdmin, preferences,
    login, logout, tryRestoreSession,
    updateProfile, uploadAvatar, destroyAvatar, updatePreferences,
  }
})
