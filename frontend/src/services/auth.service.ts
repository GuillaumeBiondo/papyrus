import axios from 'axios'
import api from './api'
import type { User } from '@/types'

const baseURL = import.meta.env.VITE_API_URL?.replace('/v1', '') ?? '/api'

export const authService = {
  csrfCookie: () =>
    axios.get(`${baseURL.replace('/api', '')}/sanctum/csrf-cookie`, {
      withCredentials: true,
    }),

  login: (email: string, password: string): Promise<{ user: User }> =>
    api.post(`${baseURL}/auth/login`, { email, password }).then((r) => r.data),

  me: (): Promise<User> =>
    api.get(`${baseURL}/auth/me`).then((r) => r.data.user),

  logout: () => api.post(`${baseURL}/auth/logout`),
}
