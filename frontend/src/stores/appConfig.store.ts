import { defineStore } from 'pinia'
import { ref } from 'vue'
import { activityService } from '@/services/activity.service'
import type { AppConfig } from '@/types'

export const useAppConfigStore = defineStore('appConfig', () => {
  const config = ref<AppConfig | null>(null)

  async function fetch() {
    if (config.value) return
    config.value = await activityService.config()
  }

  return { config, fetch }
})
