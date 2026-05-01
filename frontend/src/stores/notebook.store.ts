import { defineStore } from 'pinia'
import { ref } from 'vue'
import { notebookService } from '@/services/notebook.service'
import type { NotebookEntry } from '@/types'

export const useNotebookStore = defineStore('notebook', () => {
  const entries = ref<NotebookEntry[]>([])
  const drawerOpen = ref(false)
  const activeEntry = ref<NotebookEntry | null>(null)
  const composing = ref(false)

  function toggleDrawer() {
    drawerOpen.value = !drawerOpen.value
  }

  function openNew() {
    composing.value = true
    activeEntry.value = null
    drawerOpen.value = true
  }

  async function fetchAll(filters?: { project_id?: string; free?: boolean }) {
    const res = await notebookService.index(filters)
    entries.value = res.data
  }

  async function save(payload: Partial<NotebookEntry>) {
    if (activeEntry.value) {
      const updated = await notebookService.update(activeEntry.value.id, payload)
      const idx = entries.value.findIndex((e) => e.id === updated.id)
      if (idx !== -1) {
        if (updated.project_id) {
          entries.value.splice(idx, 1)
          activeEntry.value = null
        } else {
          entries.value[idx] = updated
          activeEntry.value = updated
        }
      }
    } else {
      const created = await notebookService.store(payload)
      entries.value.unshift(created)
      activeEntry.value = created
      composing.value = false
    }
  }

  async function remove(id: string) {
    await notebookService.destroy(id)
    entries.value = entries.value.filter((e) => e.id !== id)
    activeEntry.value = null
  }

  async function transfer(id: string, projectId: string) {
    await notebookService.transfer(id, projectId)
    entries.value = entries.value.filter((e) => e.id !== id)
    activeEntry.value = null
  }

  return {
    entries,
    drawerOpen,
    activeEntry,
    composing,
    toggleDrawer,
    openNew,
    fetchAll,
    save,
    remove,
    transfer,
  }
})
