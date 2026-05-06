import { defineStore } from 'pinia'
import { ref } from 'vue'
import { changelogService } from '@/services/changelog.service'
import type { Changelog } from '@/types'

export const useChangelogStore = defineStore('changelog', () => {
  const unread = ref<Changelog[]>([])
  const open = ref(false)

  async function fetchUnread() {
    const data = await changelogService.getUnread()
    unread.value = data.changelogs
  }

  async function markAllRead() {
    await changelogService.markAllRead()
    unread.value = []
    open.value = false
  }

  async function markRead(id: string) {
    await changelogService.markRead(id)
    unread.value = unread.value.filter(c => c.id !== id)
  }

  function openModal() {
    open.value = true
  }

  function closeModal() {
    open.value = false
  }

  return { unread, open, fetchUnread, markAllRead, markRead, openModal, closeModal }
})
