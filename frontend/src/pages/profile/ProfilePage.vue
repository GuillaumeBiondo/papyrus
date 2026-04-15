<script setup lang="ts">
import { useAuthStore } from '@/stores/auth.store'
const auth = useAuthStore()
</script>

<template>
  <div class="p-6 max-w-lg mx-auto">
    <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-6">Profil</h1>
    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl p-6">
      <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ auth.user?.name }}</p>
      <p class="text-sm text-gray-500 mt-0.5">{{ auth.user?.email }}</p>
    </div>
  </div>
</template>
