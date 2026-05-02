import axios from 'axios'
import router from '@/router'
import { recordApiCall } from '@/composables/bugBuffer'

const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL ?? '/api/v1',
  withCredentials: true,
  withXSRFToken: true,
  headers: { Accept: 'application/json' },
})

api.interceptors.request.use((config) => {
  (config as any)._sentAt = new Date().toISOString()
  return config
})

api.interceptors.response.use(
  (res) => {
    recordApiCall({
      method:   res.config.method?.toUpperCase() ?? '?',
      url:      res.config.url ?? '',
      status:   res.status,
      request:  sanitize(res.config.data ?? null),
      response: truncate(JSON.stringify(res.data)),
      at:       (res.config as any)._sentAt ?? new Date().toISOString(),
    })
    return res
  },
  (err) => {
    if (err.config) {
      recordApiCall({
        method:   err.config.method?.toUpperCase() ?? '?',
        url:      err.config.url ?? '',
        status:   err.response?.status ?? null,
        request:  sanitize(err.config.data ?? null),
        response: truncate(JSON.stringify(err.response?.data ?? null)),
        at:       (err.config as any)._sentAt ?? new Date().toISOString(),
      })
    }
    if (err.response?.status === 401) {
      router.push({ name: 'login' })
    }
    return Promise.reject(err)
  },
)

function truncate(s: string, max = 500): string {
  return s && s.length > max ? s.slice(0, max) + '…' : s
}

function sanitize(data: unknown): string {
  if (!data) return JSON.stringify(data)
  try {
    const obj = typeof data === 'string' ? JSON.parse(data) : data
    if (obj && typeof obj === 'object') {
      const SENSITIVE = ['password', 'current_password', 'password_confirmation', 'token', 'secret']
      const cleaned = { ...obj as Record<string, unknown> }
      for (const key of SENSITIVE) {
        if (key in cleaned) cleaned[key] = '***'
      }
      return truncate(JSON.stringify(cleaned))
    }
  } catch { /* not JSON, fall through */ }
  return truncate(JSON.stringify(data))
}

export default api
