import axios from 'axios'
import api from './api'
import type { User } from '@/types'

const apiUrl = import.meta.env.VITE_API_URL ?? '/api/v1'
const baseURL = apiUrl.replace(/\/v1$/, '')          // https://api.papyrus.guigeek.tech/api
const sanctumBase = apiUrl.replace(/\/api.*$/, '')   // https://api.papyrus.guigeek.tech

export const authService = {
  csrfCookie: () =>
    axios.get(`${sanctumBase}/sanctum/csrf-cookie`, {
      withCredentials: true,
    }),

  login: (email: string, password: string): Promise<{ user: User }> =>
    api.post(`${baseURL}/auth/login`, { email, password }).then((r) => r.data),

  me: (): Promise<User> =>
    api.get(`${baseURL}/auth/me`).then((r) => r.data.user),

  logout: () => api.post(`${baseURL}/auth/logout`),
}
