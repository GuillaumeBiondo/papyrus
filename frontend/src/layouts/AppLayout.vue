<script setup lang="ts">
import { onMounted } from 'vue'
import AppTopbar from '@/components/layout/AppTopbar.vue'
import ChangelogModal from '@/components/shared/ChangelogModal.vue'
import { useChangelogStore } from '@/stores/changelog.store'

const changelog = useChangelogStore()

onMounted(() => changelog.fetchUnread())
</script>

<template>
  <div class="flex flex-col h-screen bg-[#f5f4f1] dark:bg-[#0c0b18]">
    <AppTopbar />
    <main class="flex-1 overflow-y-auto relative">
      <RouterView />
      <NotebookDrawer />
    </main>
    <ChangelogModal />
  </div>
</template>
