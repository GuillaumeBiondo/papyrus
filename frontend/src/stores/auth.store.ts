import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import { authService } from '@/services/auth.service'
import type { User } from '@/types'

export const useAuthStore = defineStore('auth', () => {
  const user = ref<User | null>(null)

  const isAuthenticated = computed(() => !!user.value)
  const isBetaReader = computed(() => user.value?.currentRole === 'beta_reader')

  async function login(email: string, password: string) {
    await authService.csrfCookie()
    const data = await authService.login(email, password)
    user.value = data.user
  }

  async function logout() {
    await authService.logout()
    user.value = null
  }

  async function tryRestoreSession() {
    try {
      user.value = await authService.me()
    } catch {
      user.value = null
    }
  }

  return { user, isAuthenticated, isBetaReader, login, logout, tryRestoreSession }
})
