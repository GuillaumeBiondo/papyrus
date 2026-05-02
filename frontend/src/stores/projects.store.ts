import { defineStore } from 'pinia'
import { ref } from 'vue'
import { projectsService } from '@/services/projects.service'
import type { Project } from '@/types'

export const useProjectsStore = defineStore('projects', () => {
  const projects = ref<Project[]>([])
  const loading = ref(false)

  async function fetchAll() {
    loading.value = true
    try {
      const res = await projectsService.index()
      projects.value = res.data
    } finally {
      loading.value = false
    }
  }

  async function create(payload: Partial<Project>): Promise<Project> {
    const project = await projectsService.store(payload)
    projects.value.unshift(project)
    return project
  }

  async function update(id: string, payload: Partial<Project>): Promise<Project> {
    const updated = await projectsService.update(id, payload)
    const idx = projects.value.findIndex(p => p.id === id)
    if (idx !== -1) projects.value[idx] = updated
    return updated
  }

  async function remove(id: string): Promise<void> {
    await projectsService.destroy(id)
    projects.value = projects.value.filter(p => p.id !== id)
  }

  async function checkAccess(projectId: string): Promise<boolean> {
    if (!projects.value.length) await fetchAll()
    return projects.value.some((p) => p.id === projectId)
  }

  return { projects, loading, fetchAll, create, update, remove, checkAccess }
})
