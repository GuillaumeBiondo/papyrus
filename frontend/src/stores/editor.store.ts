import { defineStore } from 'pinia'
import { ref } from 'vue'
import { projectsService } from '@/services/projects.service'
import { chaptersService } from '@/services/chapters.service'
import { scenesService } from '@/services/scenes.service'
import type { Chapter, Project, Scene } from '@/types'

export const useEditorStore = defineStore('editor', () => {
  const currentProject = ref<Project | null>(null)
  const chapters = ref<Chapter[]>([])
  const activeScene = ref<Scene | null>(null)
  const saving = ref(false)
  const saveTimeout = ref<ReturnType<typeof setTimeout> | null>(null)

  function onContentChange(content: string) {
    if (!activeScene.value) return
    activeScene.value.content = content
    if (saveTimeout.value) clearTimeout(saveTimeout.value)
    saveTimeout.value = setTimeout(() => saveScene(), 1500)
  }

  async function saveScene() {
    if (!activeScene.value) return
    saving.value = true
    try {
      await scenesService.update(activeScene.value.id, {
        content: activeScene.value.content,
        title: activeScene.value.title,
        status: activeScene.value.status,
      })
    } finally {
      saving.value = false
    }
  }

  async function loadProject(projectId: string) {
    const [project, chaptersRes] = await Promise.all([
      projectsService.show(projectId),
      chaptersService.index(projectId),
    ])
    currentProject.value = project
    chapters.value = chaptersRes.data
  }

  function setActiveScene(scene: Scene) {
    activeScene.value = scene
  }

  function reset() {
    currentProject.value = null
    chapters.value = []
    activeScene.value = null
  }

  return {
    currentProject,
    chapters,
    activeScene,
    saving,
    onContentChange,
    saveScene,
    loadProject,
    setActiveScene,
    reset,
  }
})
