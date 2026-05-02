import api from './api'
import type { ApiCall } from '@/composables/bugBuffer'

export const bugReportService = {
  send: (payload: {
    message:        string
    url:            string
    api_calls:      ApiCall[]
    console_errors: string[]
  }) => api.post('/bug-report', payload),
}
