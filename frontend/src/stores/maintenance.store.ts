import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import api from '@/services/api'
import type { MaintenanceStatus } from '@/types'

export const useMaintenanceStore = defineStore('maintenance', () => {
  const status = ref<MaintenanceStatus | null>(null)
  const warningDismissed = ref(false)

  const isBlocked = computed(() =>
    status.value?.active === true && status.value?.user_exempt !== true,
  )

  const showWarning = computed(() =>
    status.value?.warning === true
    && !warningDismissed.value
    && status.value?.user_exempt !== true,
  )

  async function fetch() {
    try {
      const { data } = await api.get<MaintenanceStatus>('/maintenance-status')
      status.value = data
    } catch {
      // réseau indisponible — ne pas bloquer l'UI
    }
  }

  function dismissWarning() {
    warningDismissed.value = true
  }

  return { status, isBlocked, showWarning, fetch, dismissWarning }
})
