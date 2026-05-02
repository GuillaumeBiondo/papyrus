import { defineStore } from 'pinia'
import { ref } from 'vue'
import { projectsService } from '@/services/projects.service'
import { arcsService } from '@/services/arcs.service'
import { chaptersService } from '@/services/chapters.service'
import { scenesService } from '@/services/scenes.service'
import { annotationsService } from '@/services/annotations.service'
import { notesService } from '@/services/notes.service'
import { cardsService } from '@/services/cards.service'
import type { Annotation, Arc, Card, Chapter, Note, Project, Scene } from '@/types'

export const useEditorStore = defineStore('editor', () => {
  const currentProject = ref<Project | null>(null)
  const arcs = ref<Arc[]>([])
  const activeScene = ref<Scene | null>(null)
  const saving = ref(false)
  const saveTimeout = ref<ReturnType<typeof setTimeout> | null>(null)

  // Panneau droit
  const annotations = ref<Annotation[]>([])           // annotations de la scène active
  const projectNotes = ref<Note[]>([])                // notes globales du roman
  const projectCards = ref<Card[]>([])                // toutes les fiches du roman
  const sceneCards = ref<Card[]>([])                  // fiches liées à la scène active (mots-clés)
  const panelLoading = ref(false)
  const notesLoading = ref(false)
  const cardsLoading = ref(false)

  // Recherche annotations globales
  const annotationSearchQuery = ref('')
  const annotationSearchResults = ref<Annotation[]>([])
  const annotationSearchLoading = ref(false)

  // ─── Éditeur ──────────────────────────────────────────────────────────────

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
      })
      if (activeScene.value) loadSceneCards(activeScene.value.id)
    } catch (err: any) {
      console.error('[saveScene] 422 details:', err?.response?.data ?? err)
    } finally {
      saving.value = false
    }
  }

  async function setActiveScene(scene: Scene) {
    activeScene.value = scene
    await Promise.all([
      loadPanelData(scene.id),
      loadSceneCards(scene.id),
    ])
  }

  async function loadPanelData(sceneId: string) {
    panelLoading.value = true
    try {
      const annRes = await annotationsService.index(sceneId)
      annotations.value = annRes.data
    } finally {
      panelLoading.value = false
    }
  }

  async function loadSceneCards(sceneId: string) {
    cardsLoading.value = true
    try {
      const res = await cardsService.byKeywordsInScene(sceneId)
      sceneCards.value = res.data
    } finally {
      cardsLoading.value = false
    }
  }

  async function loadProjectCards(projectId: string) {
    cardsLoading.value = true
    try {
      const res = await cardsService.index(projectId, { per_page: 200 })
      projectCards.value = res.data
    } finally {
      cardsLoading.value = false
    }
  }

  async function createProjectCard(payload: { type: string; title: string }): Promise<Card> {
    if (!currentProject.value) throw new Error('No project loaded')
    const card = await cardsService.store(currentProject.value.id, payload)
    projectCards.value.push(card)
    projectCards.value.sort((a, b) => a.title.localeCompare(b.title))
    return card
  }

  async function loadProjectNotes(projectId: string) {
    notesLoading.value = true
    try {
      const res = await notesService.indexForProject(projectId)
      projectNotes.value = res.data
    } finally {
      notesLoading.value = false
    }
  }

  async function searchAnnotations(q: string) {
    if (!currentProject.value) return
    annotationSearchQuery.value = q
    if (!q.trim()) { annotationSearchResults.value = []; return }
    annotationSearchLoading.value = true
    try {
      const res = await annotationsService.searchInProject(currentProject.value.id, q)
      annotationSearchResults.value = res.data
    } finally {
      annotationSearchLoading.value = false
    }
  }

  async function setActiveSceneStatus(status: Scene['status']) {
    if (!activeScene.value) return
    activeScene.value.status = status
    await scenesService.update(activeScene.value.id, { status })
    _updateSceneInTree(activeScene.value.id, { status })
  }

  // ─── Annotations ──────────────────────────────────────────────────────────

  async function addAnnotation(
    body: string,
    type: 'inline' | 'global' = 'global',
    anchorStart?: number,
    anchorEnd?: number,
    color?: string,
  ) {
    if (!activeScene.value) return
    const ann = await annotationsService.store(activeScene.value.id, {
      body,
      type,
      anchor_start: anchorStart,
      anchor_end: anchorEnd,
      color: color ?? '#f59e0b',
    })
    annotations.value.unshift(ann)
    return ann
  }

  async function updateAnnotation(id: string, payload: { body?: string; color?: string }) {
    const updated = await annotationsService.update(id, payload)
    const idx = annotations.value.findIndex(a => a.id === id)
    if (idx !== -1) annotations.value[idx] = updated
  }

  async function removeAnnotation(id: string) {
    await annotationsService.destroy(id)
    annotations.value = annotations.value.filter(a => a.id !== id)
  }

  // ─── Notes (globales au roman) ────────────────────────────────────────────

  async function addNote(body: string) {
    if (!currentProject.value) return
    const note = await notesService.storeForProject(currentProject.value.id, { body })
    projectNotes.value.unshift(note)
    return note
  }

  async function updateNote(id: string, body: string) {
    const updated = await notesService.update(id, { body })
    const idx = projectNotes.value.findIndex(n => n.id === id)
    if (idx !== -1) projectNotes.value[idx] = updated
  }

  async function removeNote(id: string) {
    await notesService.destroy(id)
    projectNotes.value = projectNotes.value.filter(n => n.id !== id)
  }

  // ─── Chargement projet ────────────────────────────────────────────────────

  async function loadProject(projectId: string) {
    const [project, arcsRes] = await Promise.all([
      projectsService.show(projectId),
      arcsService.index(projectId),
    ])
    currentProject.value = project
    arcs.value = arcsRes.data
    loadProjectNotes(projectId)
    loadProjectCards(projectId)
  }

  // ─── Création structure ───────────────────────────────────────────────────

  async function createArc(title: string): Promise<Arc> {
    if (!currentProject.value) throw new Error('No project loaded')
    const arc = await arcsService.store(currentProject.value.id, { title, order: arcs.value.length })
    arc.chapters = []
    arcs.value.push(arc)
    return arc
  }

  async function createChapter(arcId: string, title: string): Promise<Chapter> {
    const arc = arcs.value.find(a => a.id === arcId)
    if (!arc) throw new Error('Arc not found')
    const chapter = await chaptersService.store(arcId, { title, order: arc.chapters?.length ?? 0 })
    chapter.scenes = []
    if (!arc.chapters) arc.chapters = []
    arc.chapters.push(chapter)
    return chapter
  }

  async function createScene(chapterId: string, title: string): Promise<Scene> {
    const chapter = _findChapter(chapterId)
    if (!chapter) throw new Error('Chapter not found')
    const scene = await scenesService.store(chapterId, { title, order: chapter.scenes?.length ?? 0 })
    if (!chapter.scenes) chapter.scenes = []
    chapter.scenes.push(scene)
    return scene
  }

  // ─── Suppression structure ────────────────────────────────────────────────

  async function deleteArc(arcId: string) {
    await arcsService.destroy(arcId)
    const idx = arcs.value.findIndex(a => a.id === arcId)
    if (idx !== -1) {
      const arc = arcs.value[idx]!
      const activeInArc = arc.chapters?.some(ch =>
        ch.scenes?.some(s => s.id === activeScene.value?.id),
      )
      if (activeInArc) activeScene.value = null
      arcs.value.splice(idx, 1)
    }
  }

  async function deleteChapter(chapterId: string) {
    await chaptersService.destroy(chapterId)
    for (const arc of arcs.value) {
      const idx = arc.chapters?.findIndex(c => c.id === chapterId) ?? -1
      if (idx !== -1) {
        const ch = arc.chapters![idx]!
        if (ch.scenes?.some(s => s.id === activeScene.value?.id)) activeScene.value = null
        arc.chapters!.splice(idx, 1)
        break
      }
    }
  }

  async function deleteScene(sceneId: string) {
    await scenesService.destroy(sceneId)
    if (activeScene.value?.id === sceneId) activeScene.value = null
    for (const arc of arcs.value) {
      for (const ch of arc.chapters ?? []) {
        const idx = ch.scenes?.findIndex(s => s.id === sceneId) ?? -1
        if (idx !== -1) { ch.scenes!.splice(idx, 1); return }
      }
    }
  }

  // ─── Réorganisation (drag & drop) ─────────────────────────────────────────

  async function reorderArcs() {
    if (!currentProject.value) return
    arcs.value.forEach((a, i) => { a.order = i })
    await arcsService.reorder(
      currentProject.value.id,
      arcs.value.map((a, i) => ({ id: a.id, order: i })),
    )
  }

  async function reorderChapters(arcId: string, chapters: Chapter[]) {
    chapters.forEach((c, i) => { c.order = i; c.arc_id = arcId })
    await chaptersService.reorder(
      chapters.map((c, i) => ({ id: c.id, order: i, arc_id: arcId })),
    )
  }

  async function reorderScenes(chapterId: string, scenes: Scene[]) {
    scenes.forEach((s, i) => { s.order = i; s.chapter_id = chapterId })
    await scenesService.reorder(
      scenes.map((s, i) => ({ id: s.id, order: i, chapter_id: chapterId })),
    )
  }

  // ─── Helpers privés ───────────────────────────────────────────────────────

  function _findChapter(chapterId: string): Chapter | undefined {
    for (const arc of arcs.value) {
      const ch = arc.chapters?.find(c => c.id === chapterId)
      if (ch) return ch
    }
  }

  function _updateSceneInTree(sceneId: string, patch: Partial<Scene>) {
    for (const arc of arcs.value) {
      for (const ch of arc.chapters ?? []) {
        const s = ch.scenes?.find(s => s.id === sceneId)
        if (s) { Object.assign(s, patch); return }
      }
    }
  }

  async function renameArc(arcId: string, title: string) {
    const arc = arcs.value.find(a => a.id === arcId)
    if (arc) arc.title = title
    await arcsService.update(arcId, { title })
  }

  async function renameChapter(chapterId: string, title: string) {
    const chapter = _findChapter(chapterId)
    if (chapter) chapter.title = title
    await chaptersService.update(chapterId, { title })
  }

  async function renameScene(sceneId: string, title: string) {
    _updateSceneInTree(sceneId, { title })
    await scenesService.update(sceneId, { title })
  }

  function reset() {
    currentProject.value = null
    arcs.value = []
    activeScene.value = null
    annotations.value = []
    projectNotes.value = []
    projectCards.value = []
    sceneCards.value = []
    annotationSearchQuery.value = ''
    annotationSearchResults.value = []
    if (saveTimeout.value) clearTimeout(saveTimeout.value)
  }

  return {
    currentProject, arcs, activeScene, saving,
    annotations, projectNotes, projectCards, sceneCards, panelLoading, notesLoading, cardsLoading,
    annotationSearchQuery, annotationSearchResults, annotationSearchLoading,
    onContentChange, saveScene, loadProject,
    setActiveScene, setActiveSceneStatus,
    addAnnotation, updateAnnotation, removeAnnotation,
    addNote, updateNote, removeNote,
    searchAnnotations,
    createProjectCard,
    createArc, createChapter, createScene,
    deleteArc, deleteChapter, deleteScene,
    reorderArcs, reorderChapters, reorderScenes,
    renameArc, renameChapter, renameScene,
    reset,
  }
})
