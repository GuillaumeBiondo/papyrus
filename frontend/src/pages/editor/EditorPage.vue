<script setup lang="ts">
import { onMounted, onUnmounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import { useEditorStore } from '@/stores/editor.store'
import SceneEditor from '@/components/editor/SceneEditor.vue'
import EditorRightPanel from '@/components/editor/EditorRightPanel.vue'
import ConfirmDeleteDialog from '@/components/editor/ConfirmDeleteDialog.vue'
import CardEditDialog from '@/components/editor/CardEditDialog.vue'
import Splitter from 'primevue/splitter'
import SplitterPanel from 'primevue/splitterpanel'
import draggable from 'vuedraggable'
import type { Arc, Chapter, Scene } from '@/types'

const route = useRoute()
const editor = useEditorStore()

const vFocus = { mounted: (el: HTMLElement) => el.focus() }

// ── Mobile detection ──────────────────────────────────────────
const isMobile = ref(window.innerWidth < 768)
function onResize() { isMobile.value = window.innerWidth < 768 }

// ── Sidebar visibility ────────────────────────────────────────
const leftSidebarOpen = ref(!isMobile.value)
const rightSidebarOpen = ref(!isMobile.value)

// ── Panneau droit ─────────────────────────────────────────────
const rightTab = ref<'annotations' | 'notes' | 'fiches'>('annotations')

// ── Drag & Drop ───────────────────────────────────────────────
const addingArc = ref(false)
const newArcTitle = ref('')
const addingChapter = ref<string | null>(null)
const newChapterTitle = ref('')
const addingScene = ref<string | null>(null)
const newSceneTitle = ref('')

const editingItemId = ref<string | null>(null)
const editingTitleVal = ref('')

function onChapterDragChange(event: { added?: unknown; moved?: unknown }, arc: Arc) {
  if (event.added || event.moved) editor.reorderChapters(arc.id, arc.chapters ?? [])
}
function onSceneDragChange(event: { added?: unknown; moved?: unknown }, chapter: Chapter) {
  if (event.added || event.moved) editor.reorderScenes(chapter.id, chapter.scenes ?? [])
}

// ── Suppression avec confirmation ─────────────────────────────
type PendingDelete = {
  type: 'arc' | 'chapter' | 'scene'
  id: string; title: string; dialogTitle: string; message: string
  counts: { label: string; value: number }[]
}
const pendingDelete = ref<PendingDelete | null>(null)
const deleteLoading = ref(false)
const deleteBlockMessage = ref<string | null>(null)

function isSceneProtected(scene: Scene) {
  return scene.status === 'revised' || scene.status === 'final'
}

function askDeleteArc(arc: Arc) {
  const scenes = arc.chapters?.flatMap(ch => ch.scenes ?? []) ?? []
  const protectedScene = scenes.find(isSceneProtected)
  if (protectedScene) {
    deleteBlockMessage.value = `Impossible de supprimer cet arc : il contient des scènes révisées ou finalisées (ex. « ${protectedScene.title} »). Remets-les à l'état brouillon ou idée avant de supprimer.`
    return
  }
  const chaptersCount = arc.chapters?.length ?? 0
  const scenesCount = scenes.length
  pendingDelete.value = {
    type: 'arc', id: arc.id, title: arc.title,
    dialogTitle: `Supprimer l'arc « ${arc.title} » ?`,
    message: 'Cette action est irréversible. Toutes les annotations et notes associées seront supprimées.',
    counts: [{ label: 'Chapitres', value: chaptersCount }, { label: 'Scènes', value: scenesCount }],
  }
}

function askDeleteChapter(chapter: Chapter) {
  const protectedScene = chapter.scenes?.find(isSceneProtected)
  if (protectedScene) {
    deleteBlockMessage.value = `Impossible de supprimer ce chapitre : il contient des scènes révisées ou finalisées (ex. « ${protectedScene.title} »). Remets-les à l'état brouillon ou idée avant de supprimer.`
    return
  }
  pendingDelete.value = {
    type: 'chapter', id: chapter.id, title: chapter.title,
    dialogTitle: `Supprimer le chapitre « ${chapter.title} » ?`,
    message: 'Cette action est irréversible. Toutes les annotations et notes associées seront supprimées.',
    counts: [{ label: 'Scènes', value: chapter.scenes?.length ?? 0 }],
  }
}

function askDeleteScene(scene: Scene) {
  if (isSceneProtected(scene)) {
    deleteBlockMessage.value = `La scène « ${scene.title} » est ${scene.status === 'final' ? 'finalisée' : 'révisée'} et ne peut pas être supprimée. Remets-la à l'état brouillon ou idée avant de supprimer.`
    return
  }
  pendingDelete.value = {
    type: 'scene', id: scene.id, title: scene.title,
    dialogTitle: `Supprimer la scène « ${scene.title} » ?`,
    message: 'Cette action est irréversible. Toutes les annotations et notes de cette scène seront supprimées.',
    counts: [],
  }
}

async function confirmDelete() {
  if (!pendingDelete.value) return
  deleteLoading.value = true
  try {
    const { type, id } = pendingDelete.value
    if (type === 'arc') await editor.deleteArc(id)
    else if (type === 'chapter') await editor.deleteChapter(id)
    else await editor.deleteScene(id)
    pendingDelete.value = null
  } finally {
    deleteLoading.value = false
  }
}

// ── Sélection de texte (inline annotations) ───────────────────
const pendingSelection = ref<{ from: number; to: number; text: string } | null>(null)

function onAnnotate(sel: { from: number; to: number; text: string }) {
  pendingSelection.value = sel
  rightTab.value = 'annotations'
  if (isMobile.value) rightSidebarOpen.value = true
}

// ── Annotation ciblée (clic sur surligné) ────────────────────
const highlightedAnnotationId = ref<string | null>(null)

function onAnnotationClick(id: string) {
  rightTab.value = 'annotations'
  highlightedAnnotationId.value = id
  rightSidebarOpen.value = true
  setTimeout(() => {
    document.getElementById(`ann-${id}`)?.scrollIntoView({ behavior: 'smooth', block: 'nearest' })
    setTimeout(() => { highlightedAnnotationId.value = null }, 1800)
  }, 50)
}

// ── TipTap / SceneEditor ──────────────────────────────────────
const sceneEditorRef = ref<InstanceType<typeof SceneEditor> | null>(null)
function tiptap() { return sceneEditorRef.value?.editor }

function focusAnnotation(anchorStart: number, anchorEnd: number) {
  sceneEditorRef.value?.focusAnnotation(anchorStart, anchorEnd)
}

function getAnchorText(ann: { anchor_start: number | null; anchor_end: number | null }): string {
  if (ann.anchor_start == null || ann.anchor_end == null) return ''
  try { return tiptap()?.state.doc.textBetween(ann.anchor_start, ann.anchor_end, ' ') ?? '' }
  catch { return '' }
}

// ── Fiches dialog ─────────────────────────────────────────────
const selectedCardId = ref<string | null>(null)

function navigateToSceneById(sceneId: string) {
  for (const arc of editor.arcs) {
    for (const ch of arc.chapters ?? []) {
      const scene = ch.scenes?.find(s => s.id === sceneId)
      if (scene) { editor.setActiveScene(scene); selectedCardId.value = null; return }
    }
  }
}

// ── Lifecycle ─────────────────────────────────────────────────
onMounted(() => {
  editor.loadProject(route.params.projectId as string)
  window.addEventListener('resize', onResize)
})
onUnmounted(() => {
  editor.reset()
  window.removeEventListener('resize', onResize)
})

// ── Création ─────────────────────────────────────────────────
async function submitArc() {
  const title = newArcTitle.value.trim()
  if (!title) return
  await editor.createArc(title)
  newArcTitle.value = ''
  addingArc.value = false
}

async function submitChapter(arcId: string) {
  const title = newChapterTitle.value.trim()
  if (!title) return
  await editor.createChapter(arcId, title)
  newChapterTitle.value = ''
  addingChapter.value = null
}

async function submitScene(chapterId: string) {
  const title = newSceneTitle.value.trim()
  if (!title) return
  const scene = await editor.createScene(chapterId, title)
  newSceneTitle.value = ''
  addingScene.value = null
  editor.setActiveScene(scene)
  if (isMobile.value) leftSidebarOpen.value = false
}

// ── Renommage inline ──────────────────────────────────────────
function startRename(id: string, currentTitle: string) {
  editingItemId.value = id
  editingTitleVal.value = currentTitle
}

async function commitRename(type: 'arc' | 'chapter' | 'scene', id: string) {
  if (editingItemId.value !== id) return
  const title = editingTitleVal.value.trim()
  editingItemId.value = null
  if (!title) return
  if (type === 'arc') await editor.renameArc(id, title)
  else if (type === 'chapter') await editor.renameChapter(id, title)
  else await editor.renameScene(id, title)
}

function cancelRename() {
  editingItemId.value = null
}

function onSceneTitleBlur(event: FocusEvent) {
  const t = (event.target as HTMLElement).innerText.trim()
  if (!t || !editor.activeScene) return
  editor.renameScene(editor.activeScene.id, t)
}

// ── Helpers statut ────────────────────────────────────────────
const STATUS_LABEL: Record<Scene['status'], string> = {
  idea: 'idée', draft: 'brouillon', revised: 'révisé', final: 'final',
}
const STATUS_DOT: Record<Scene['status'], string> = {
  idea: 'bg-gray-300 dark:bg-gray-600',
  draft: 'bg-amber-400',
  revised: 'bg-blue-400',
  final: 'bg-green-400',
}

// ── Passthrough Splitter ──────────────────────────────────────
const splitterPt = {
  root: { class: 'flex h-full overflow-hidden' },
  gutter: { class: 'hidden md:flex items-center justify-center shrink-0 cursor-col-resize w-1.5 group bg-gray-100 dark:bg-gray-800 hover:bg-brand-200 dark:hover:bg-brand-900/40 transition-colors' },
  gutterHandle: { class: 'h-10 w-1 rounded-full bg-gray-300 dark:bg-gray-600 group-hover:bg-brand-500 transition-colors' },
}
const panelPt = { root: { class: 'flex flex-col overflow-hidden min-w-0 h-full' } }
const rightPanelPt = { root: { class: 'flex flex-col overflow-hidden h-full border-l border-gray-300 dark:border-gray-700' } }
</script>

<template>
  <div class="flex h-full overflow-hidden relative">

    <!-- ── Backdrop mobile ── -->
    <Transition name="fade">
      <div
        v-if="isMobile && (leftSidebarOpen || rightSidebarOpen)"
        class="fixed inset-0 z-30 bg-black/50 md:hidden"
        @click="leftSidebarOpen = false; rightSidebarOpen = false"
      />
    </Transition>

    <!-- ══════════════════════════════════════
         SIDEBAR GAUCHE — drawer mobile / inline desktop
    ═══════════════════════════════════════ -->
    <aside
      :class="[
        'flex flex-col border-r border-gray-300 dark:border-gray-700 bg-[#f0efe9] dark:bg-gray-900 overflow-hidden',
        'fixed inset-y-0 left-0 z-40 w-64 transition-transform duration-300',
        'md:relative md:z-auto md:w-56 md:shrink-0 md:transition-none',
        leftSidebarOpen ? 'translate-x-0' : '-translate-x-full md:hidden',
      ]"
    >
      <!-- En-tête projet -->
      <div class="px-4 py-3 border-b border-gray-300 dark:border-gray-700 flex items-center justify-between gap-2 shrink-0">
        <div class="min-w-0">
          <p class="text-xs font-semibold text-gray-800 dark:text-gray-100 truncate">{{ editor.currentProject?.title }}</p>
          <p class="text-xs text-gray-400 mt-0.5">{{ editor.arcs.length }} arc{{ editor.arcs.length !== 1 ? 's' : '' }}</p>
        </div>
        <button
          class="shrink-0 p-1 rounded text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-800 transition-colors"
          @click="leftSidebarOpen = false"
        >
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
          </svg>
        </button>
      </div>

      <!-- Arcs -->
      <div class="flex-1 overflow-y-auto py-2">
        <draggable v-model="editor.arcs" item-key="id" handle=".drag-arc" :animation="150" ghost-class="opacity-30" @end="editor.reorderArcs()">
          <template #item="{ element: arc }">
            <div class="mb-2">
              <div class="flex items-center gap-1 px-2 py-1 group">
                <span class="drag-arc shrink-0 cursor-grab text-gray-400 dark:text-gray-500 md:opacity-0 group-hover:opacity-100 transition-opacity select-none text-base leading-none" title="Déplacer">⠿</span>
                <input
                  v-if="editingItemId === arc.id"
                  v-focus
                  v-model="editingTitleVal"
                  type="text"
                  class="flex-1 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide rounded border border-brand-300 dark:border-brand-600 bg-white dark:bg-gray-800 px-1 py-0 focus:outline-none focus:ring-1 focus:ring-brand-500 min-w-0"
                  @keyup.enter="commitRename('arc', arc.id)"
                  @keyup.escape="cancelRename"
                  @blur="commitRename('arc', arc.id)"
                />
                <span v-else class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide truncate flex-1">{{ arc.title }}</span>
                <div class="flex items-center gap-0.5 md:opacity-0 group-hover:opacity-100 transition-opacity shrink-0">
                  <button class="px-1.5 py-0.5 text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-100" @click="addingChapter = arc.id; newChapterTitle = ''">+</button>
                  <button class="px-1.5 py-0.5 text-xs text-gray-500 dark:text-gray-400 hover:text-brand-600 dark:hover:text-brand-400 transition-colors" @click.stop="startRename(arc.id, arc.title)">✎</button>
                  <button class="px-1.5 py-0.5 text-xs text-gray-500 dark:text-gray-400 hover:text-red-500 dark:hover:text-red-400 transition-colors" @click="askDeleteArc(arc)">✕</button>
                </div>
              </div>

              <draggable :list="arc.chapters" item-key="id" :group="{ name: 'chapters', pull: true, put: ['chapters'] }" handle=".drag-chapter" :animation="150" ghost-class="opacity-30" class="ml-2"
                @change="(e: { added?: unknown; moved?: unknown }) => onChapterDragChange(e, arc)">
                <template #item="{ element: chapter }">
                  <div class="mb-1">
                    <div class="flex items-center gap-1 px-2 py-0.5 group">
                      <span class="drag-chapter shrink-0 cursor-grab text-gray-400 dark:text-gray-500 md:opacity-0 group-hover:opacity-100 transition-opacity select-none text-base leading-none">⠿</span>
                      <input
                        v-if="editingItemId === chapter.id"
                        v-focus
                        v-model="editingTitleVal"
                        type="text"
                        class="flex-1 text-xs text-gray-500 dark:text-gray-400 italic rounded border border-brand-300 dark:border-brand-600 bg-white dark:bg-gray-800 px-1 py-0 focus:outline-none focus:ring-1 focus:ring-brand-500 min-w-0"
                        @keyup.enter="commitRename('chapter', chapter.id)"
                        @keyup.escape="cancelRename"
                        @blur="commitRename('chapter', chapter.id)"
                      />
                      <span v-else class="text-xs text-gray-500 dark:text-gray-400 italic truncate flex-1">{{ chapter.title }}</span>
                      <div class="flex items-center gap-0.5 md:opacity-0 group-hover:opacity-100 transition-opacity shrink-0">
                        <button class="px-1.5 py-0.5 text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-100" @click="addingScene = chapter.id; newSceneTitle = ''">+</button>
                        <button class="px-1.5 py-0.5 text-xs text-gray-500 dark:text-gray-400 hover:text-brand-600 dark:hover:text-brand-400 transition-colors" @click.stop="startRename(chapter.id, chapter.title)">✎</button>
                        <button class="px-1.5 py-0.5 text-xs text-gray-500 dark:text-gray-400 hover:text-red-500 dark:hover:text-red-400 transition-colors" @click="askDeleteChapter(chapter)">✕</button>
                      </div>
                    </div>

                    <draggable :list="chapter.scenes" item-key="id" :group="{ name: 'scenes', pull: true, put: ['scenes'] }" handle=".drag-scene" :animation="150" ghost-class="opacity-30"
                      @change="(e: { added?: unknown; moved?: unknown }) => onSceneDragChange(e, chapter)">
                      <template #item="{ element: scene }">
                        <div
                          class="flex items-center gap-1 pl-3 pr-2 rounded-md group/scene transition-colors"
                          :class="editor.activeScene?.id === scene.id ? 'bg-brand-50 dark:bg-brand-900/20' : 'hover:bg-gray-100 dark:hover:bg-gray-800'"
                        >
                          <span class="drag-scene shrink-0 cursor-grab text-gray-400 dark:text-gray-500 md:opacity-0 group-hover/scene:opacity-100 transition-opacity select-none text-base leading-none">⠿</span>
                          <input
                            v-if="editingItemId === scene.id"
                            v-focus
                            v-model="editingTitleVal"
                            type="text"
                            class="flex-1 text-xs rounded border border-brand-300 dark:border-brand-600 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 px-1 py-0.5 focus:outline-none focus:ring-1 focus:ring-brand-500 min-w-0"
                            @keyup.enter="commitRename('scene', scene.id)"
                            @keyup.escape="cancelRename"
                            @blur="commitRename('scene', scene.id)"
                          />
                          <button
                            v-else
                            class="flex-1 text-left flex items-center gap-2 py-1.5 min-w-0"
                            :class="editor.activeScene?.id === scene.id ? 'text-brand-700 dark:text-brand-300' : 'text-gray-600 dark:text-gray-400'"
                            @click="editor.setActiveScene(scene); if (isMobile) leftSidebarOpen = false"
                          >
                            <span class="w-1.5 h-1.5 rounded-full shrink-0" :class="STATUS_DOT[scene.status as Scene['status']]" />
                            <span class="text-xs truncate flex-1">{{ scene.title }}</span>
                            <span class="text-xs text-gray-400 shrink-0">{{ scene.word_count }}</span>
                          </button>
                          <button class="shrink-0 px-1.5 py-0.5 text-xs text-gray-500 dark:text-gray-400 hover:text-brand-600 dark:hover:text-brand-400 md:opacity-0 group-hover/scene:opacity-100 transition-all" @click.stop="startRename(scene.id, scene.title)">✎</button>
                          <button class="shrink-0 px-1.5 py-0.5 text-xs text-gray-500 dark:text-gray-400 hover:text-red-500 dark:hover:text-red-400 md:opacity-0 group-hover/scene:opacity-100 transition-all" @click="askDeleteScene(scene)">✕</button>
                        </div>
                      </template>
                    </draggable>

                    <div v-if="addingScene === chapter.id" class="pl-4 pr-2 mt-1">
                      <input v-model="newSceneTitle" type="text" placeholder="Titre de la scène…"
                        class="w-full text-xs rounded border border-brand-300 dark:border-brand-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 px-2 py-1 focus:outline-none focus:ring-1 focus:ring-brand-500"
                        v-focus @keyup.enter="submitScene(chapter.id)" @keyup.escape="addingScene = null" />
                      <div class="flex gap-2 mt-1">
                        <button class="text-xs text-brand-600 dark:text-brand-400 hover:text-brand-700 dark:hover:text-brand-300 font-medium" @click="submitScene(chapter.id)">Créer</button>
                        <button class="text-xs text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200" @click="addingScene = null">Annuler</button>
                      </div>
                    </div>
                    <button v-if="!chapter.scenes?.length && addingScene !== chapter.id" class="w-full text-left text-xs text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 pl-4 pr-2 py-1" @click="addingScene = chapter.id; newSceneTitle = ''">+ scène</button>
                  </div>
                </template>
              </draggable>

              <div v-if="addingChapter === arc.id" class="ml-2 px-2 mt-1">
                <input v-model="newChapterTitle" type="text" placeholder="Titre du chapitre…"
                  class="w-full text-xs rounded border border-brand-300 dark:border-brand-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 px-2 py-1 focus:outline-none focus:ring-1 focus:ring-brand-500"
                  v-focus @keyup.enter="submitChapter(arc.id)" @keyup.escape="addingChapter = null" />
                <div class="flex gap-2 mt-1">
                  <button class="text-xs text-brand-600 dark:text-brand-400 hover:text-brand-700 dark:hover:text-brand-300 font-medium" @click="submitChapter(arc.id)">Créer</button>
                  <button class="text-xs text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200" @click="addingChapter = null">Annuler</button>
                </div>
              </div>
              <button v-if="!arc.chapters?.length && addingChapter !== arc.id" class="w-full text-left text-xs text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 px-3 py-1" @click="addingChapter = arc.id; newChapterTitle = ''">+ chapitre</button>
            </div>
          </template>
        </draggable>

        <div v-if="addingArc" class="px-3 mt-2">
          <input v-model="newArcTitle" type="text" placeholder="Titre de l'arc…"
            class="w-full text-xs rounded border border-brand-300 dark:border-brand-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 px-2 py-1 focus:outline-none focus:ring-1 focus:ring-brand-500"
            v-focus @keyup.enter="submitArc" @keyup.escape="addingArc = false" />
          <div class="flex gap-2 mt-1">
            <button class="text-xs text-brand-600 dark:text-brand-400 hover:text-brand-700 dark:hover:text-brand-300 font-medium" @click="submitArc">Créer</button>
            <button class="text-xs text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200" @click="addingArc = false">Annuler</button>
          </div>
        </div>
      </div>

      <div class="px-3 py-3 border-t border-gray-300 dark:border-gray-700 shrink-0">
        <button class="w-full text-xs text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 flex items-center gap-1.5 transition-colors" @click="addingArc = true">
          <span class="text-base leading-none">+</span> Nouvel arc
        </button>
      </div>
    </aside>

    <!-- Bandeau expansion sidebar (desktop uniquement) -->
    <div
      v-if="!leftSidebarOpen"
      class="hidden md:flex shrink-0 w-8 flex-col items-center border-r border-gray-300 dark:border-gray-700 bg-[#f0efe9] dark:bg-gray-900 py-3"
    >
      <button class="p-1 rounded text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-800 transition-colors" @click="leftSidebarOpen = true">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
        </svg>
      </button>
    </div>

    <!-- ══════════════════════════════════════
         ZONE CENTRALE + PANNEAU DROIT
    ═══════════════════════════════════════ -->
    <Splitter
      :key="`splitter-${rightSidebarOpen && !isMobile}-${!!editor.activeScene}`"
      class="flex-1 min-w-0"
      :pt="splitterPt"
    >

      <!-- ── Panneau éditeur ── -->
      <SplitterPanel :size="isMobile ? 100 : 72" :minSize="30" :pt="panelPt">

        <!-- Barre d'outils — toujours visible pour les boutons mobile -->
        <div class="flex items-center gap-2 px-3 md:px-4 h-12 md:h-10 border-b border-gray-300 dark:border-gray-700 shrink-0">

          <!-- Bouton structure (mobile, toujours visible) -->
          <button
            class="md:hidden p-1.5 rounded-lg text-gray-500 hover:text-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
            title="Structure"
            @click="leftSidebarOpen = true"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"/>
            </svg>
          </button>

          <template v-if="editor.activeScene">
            <!-- Statut scène -->
            <div class="flex items-center gap-1">
              <button
                v-for="(label, val) in ({ idea: 'I', draft: 'B', revised: 'R', final: 'F' } as Record<Scene['status'], string>)"
                :key="val"
                class="w-6 h-6 text-xs rounded font-medium transition-colors"
                :class="editor.activeScene.status === val ? 'bg-brand-600 text-white' : 'text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800'"
                :title="STATUS_LABEL[val as Scene['status']]"
                @click="editor.setActiveSceneStatus(val as Scene['status'])"
              >{{ label }}</button>
            </div>

            <span class="ml-auto text-xs text-gray-400 hidden md:inline">
              {{ editor.saving ? 'Enregistrement…' : `${editor.activeScene.word_count ?? 0} mots` }}
            </span>
          </template>

          <span v-else class="flex-1" />

          <!-- Bouton panneau droit (desktop) -->
          <button
            v-if="!rightSidebarOpen"
            class="hidden md:flex p-1 rounded text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
            @click="rightSidebarOpen = true"
          >
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
          </button>

          <!-- Bouton annotations (mobile, toujours visible) -->
          <button
            class="md:hidden ml-auto p-1.5 rounded-lg text-gray-500 hover:text-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
            title="Annotations"
            @click="rightSidebarOpen = true"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/>
            </svg>
          </button>
        </div>

        <!-- Zone centrale -->
        <div v-if="!editor.activeScene" class="flex-1 flex items-center justify-center">
          <p class="text-sm text-gray-400">
            {{ editor.arcs.length ? 'Sélectionne une scène pour commencer.' : 'Crée ton premier arc.' }}
          </p>
        </div>

        <template v-else>
          <!-- Éditeur TipTap -->
          <div class="flex-1 overflow-y-auto px-4 md:px-8 py-6">
            <h1
              :key="editor.activeScene.id"
              class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-6 outline-none empty:before:content-[attr(data-placeholder)] empty:before:text-gray-400"
              contenteditable
              data-placeholder="Titre de la scène…"
              @blur="onSceneTitleBlur"
            >{{ editor.activeScene.title }}</h1>
            <SceneEditor
              ref="sceneEditorRef"
              :content="editor.activeScene.content ?? ''"
              :annotations="editor.annotations"
              @change="editor.onContentChange"
              @annotate="onAnnotate"
              @annotation-click="onAnnotationClick"
            />
          </div>
        </template>
      </SplitterPanel>

      <!-- ── Panneau droit (desktop uniquement) ── -->
      <SplitterPanel
        v-if="rightSidebarOpen && !isMobile"
        :size="28"
        :minSize="15"
        :pt="rightPanelPt"
      >
        <EditorRightPanel
          v-model:tab="rightTab"
          :pending-selection="pendingSelection"
          :highlighted-annotation-id="highlightedAnnotationId"
          :get-anchor-text="getAnchorText"
          @close="rightSidebarOpen = false"
          @card-selected="selectedCardId = $event"
          @focus-annotation="(as, ae) => focusAnnotation(as, ae)"
          @discard-selection="pendingSelection = null"
        />
      </SplitterPanel>
    </Splitter>

    <!-- ══ Drawer mobile droit (bottom sheet) ══ -->
    <Transition name="slide-up">
      <div
        v-if="rightSidebarOpen && isMobile"
        class="fixed inset-x-0 bottom-0 z-40 flex flex-col bg-white dark:bg-gray-900 rounded-t-2xl shadow-2xl overflow-hidden border-t border-gray-200 dark:border-gray-700"
        style="height: 70dvh"
      >
        <!-- Indicateur de glissement -->
        <div class="flex justify-center pt-2 pb-1 shrink-0">
          <div class="w-10 h-1 rounded-full bg-gray-300 dark:bg-gray-600" />
        </div>
        <EditorRightPanel
          v-model:tab="rightTab"
          :pending-selection="pendingSelection"
          :highlighted-annotation-id="highlightedAnnotationId"
          :get-anchor-text="getAnchorText"
          @close="rightSidebarOpen = false"
          @card-selected="selectedCardId = $event"
          @focus-annotation="(as, ae) => focusAnnotation(as, ae)"
          @discard-selection="pendingSelection = null"
        />
      </div>
    </Transition>

  </div>

  <!-- ══ Dialogue de confirmation de suppression ══ -->
  <!-- ══ Dialog blocage suppression ══ -->
  <Teleport to="body">
    <div v-if="deleteBlockMessage" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm" @click.self="deleteBlockMessage = null">
      <div class="bg-white dark:bg-gray-900 rounded-xl shadow-2xl w-full max-w-sm border border-gray-200 dark:border-gray-700">
        <div class="flex items-start gap-3 p-5 border-b border-gray-100 dark:border-gray-800">
          <div class="mt-0.5 shrink-0 w-9 h-9 rounded-full bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center">
            <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v-4m0-4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
            </svg>
          </div>
          <div>
            <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Suppression impossible</h3>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ deleteBlockMessage }}</p>
          </div>
        </div>
        <div class="flex p-4 border-t border-gray-100 dark:border-gray-800">
          <button class="flex-1 px-4 py-2 text-xs rounded-lg bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors font-medium" @click="deleteBlockMessage = null">Compris</button>
        </div>
      </div>
    </div>
  </Teleport>

  <ConfirmDeleteDialog
    v-if="pendingDelete"
    :title="pendingDelete.dialogTitle"
    :message="pendingDelete.message"
    :counts="pendingDelete.counts"
    :loading="deleteLoading"
    @confirm="confirmDelete"
    @cancel="pendingDelete = null"
  />

  <!-- ══ Dialogue d'édition de fiche ══ -->
  <CardEditDialog
    :card-id="selectedCardId"
    :project-id="editor.currentProject?.id ?? ''"
    @close="selectedCardId = null"
    @navigate-to-scene="navigateToSceneById"
  />
</template>

<style>
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }

.slide-up-enter-active, .slide-up-leave-active { transition: transform 0.3s ease; }
.slide-up-enter-from, .slide-up-leave-to { transform: translateY(100%); }
</style>
