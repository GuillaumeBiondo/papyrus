<script setup lang="ts">
import AppTopbar from '@/components/layout/AppTopbar.vue'
</script>

<template>
  <div class="flex flex-col h-screen bg-white dark:bg-gray-950">
    <AppTopbar />
    <main class="flex-1 overflow-hidden relative">
      <RouterView />
      <NotebookDrawer />
    </main>
  </div>
</template>
