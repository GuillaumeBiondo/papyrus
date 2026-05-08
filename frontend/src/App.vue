<script setup lang="ts">
import { onMounted } from 'vue'
import { RouterView } from 'vue-router'
import MaintenanceBlockedModal from '@/components/shared/MaintenanceBlockedModal.vue'
import MaintenanceWarningBanner from '@/components/shared/MaintenanceWarningBanner.vue'
import { useMaintenanceStore } from '@/stores/maintenance.store'

const maintenance = useMaintenanceStore()

onMounted(() => maintenance.fetch())
</script>

<template>
  <MaintenanceWarningBanner />
  <MaintenanceBlockedModal />
  <RouterView />
</template>
