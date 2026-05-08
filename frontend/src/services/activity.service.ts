import api from './api'
import type { ActivityDay, ActivityHour, AppConfig } from '@/types'

interface ActivityData {
  daily:  ActivityDay[]
  hourly: ActivityHour[]
}

export const activityService = {
  global(): Promise<ActivityData> {
    return api.get('/activity').then(r => r.data)
  },

  forProject(projectId: string): Promise<ActivityData> {
    return api.get(`/projects/${projectId}/activity`).then(r => r.data)
  },

  config(): Promise<AppConfig> {
    return api.get('/config').then(r => r.data)
  },
}
