import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { changelogService } from '@/services/changelog.service'
import type { Changelog } from '@/types'

type ChangelogEntry = Changelog & { read: boolean }

export const useChangelogStore = defineStore('changelog', () => {
  const all = ref<ChangelogEntry[]>([])
  const open = ref(false)
  const loaded = ref(false)

  const unreadCount = computed(() => all.value.filter(c => !c.read).length)
  const hasAny = computed(() => loaded.value)

  async function fetchAll() {
    try {
      const data = await changelogService.getAll()
      all.value = data.changelogs
    } catch {
      // Fallback : récupère au moins les non-lus
      try {
        const data = await changelogService.getUnread()
        all.value = data.changelogs.map(c => ({ ...c, read: false }))
      } catch { /* silencieux */ }
    } finally {
      loaded.value = true
    }
  }

  async function fetchUnread() {
    await fetchAll()
  }

  async function markAllRead() {
    await changelogService.markAllRead()
    all.value = all.value.map(c => ({ ...c, read: true }))
    open.value = false
  }

  async function markRead(id: string) {
    await changelogService.markRead(id)
    const entry = all.value.find(c => c.id === id)
    if (entry) entry.read = true
  }

  function openModal() { open.value = true }
  function closeModal() { open.value = false }

  // Legacy compat
  const unread = computed(() => all.value.filter(c => !c.read))

  return { all, unread, unreadCount, hasAny, open, loaded, fetchAll, fetchUnread, markAllRead, markRead, openModal, closeModal }
})
