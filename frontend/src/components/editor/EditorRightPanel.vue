<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { useEditorStore } from '@/stores/editor.store'
import { useAuthStore } from '@/stores/auth.store'
import { cardsService } from '@/services/cards.service'
import { todosService } from '@/services/todos.service'
import CardInlineView from './CardInlineView.vue'
import type { Annotation, Card, Todo } from '@/types'

const props = defineProps<{
  tab: 'annotations' | 'notes' | 'fiches' | 'todos'
  pendingSelection: { from: number; to: number; text: string } | null
  highlightedAnnotationId: string | null
  getAnchorText: (ann: { anchor_start: number | null; anchor_end: number | null }) => string
}>()

const emit = defineEmits<{
  'update:tab': [tab: 'annotations' | 'notes' | 'fiches' | 'todos']
  close: []
  'card-selected': [id: string]
  'focus-annotation': [anchor_start: number, anchor_end: number]
  'discard-selection': []
}>()

const vFocus = { mounted: (el: HTMLElement) => el.focus() }
const editor = useEditorStore()
const auth   = useAuthStore()

// ── Couleurs annotations ───────────────────────────────────────
const ANNOTATION_COLORS = [
  { value: '#f59e0b', label: 'Ambre' },
  { value: '#ef4444', label: 'Rouge' },
  { value: '#10b981', label: 'Vert' },
  { value: '#3b82f6', label: 'Bleu' },
  { value: '#8b5cf6', label: 'Violet' },
  { value: '#ec4899', label: 'Rose' },
]
const selectedColor = ref(ANNOTATION_COLORS[0]?.value ?? '#f59e0b')

// ── Annotations ───────────────────────────────────────────────
const showGlobalForm = ref(false)
const newAnnotationBody = ref('')
const inlineAnnotationBody = ref('')
const editingAnnotationId = ref<string | null>(null)
const editingAnnotationBody = ref('')
const editingAnnotationColor = ref('')

function startEditAnnotation(ann: { id: string; body: string; color: string }) {
  editingAnnotationId.value = ann.id
  editingAnnotationBody.value = ann.body
  editingAnnotationColor.value = ann.color
}

async function saveEditAnnotation() {
  if (!editingAnnotationId.value || !editingAnnotationBody.value.trim()) return
  await editor.updateAnnotation(editingAnnotationId.value, {
    body: editingAnnotationBody.value.trim(),
    color: editingAnnotationColor.value,
  })
  editingAnnotationId.value = null
}

async function submitGlobalAnnotation() {
  if (!newAnnotationBody.value.trim()) return
  await editor.addAnnotation(newAnnotationBody.value.trim(), 'global', undefined, undefined, selectedColor.value)
  newAnnotationBody.value = ''
  showGlobalForm.value = false
}

async function submitInlineAnnotation() {
  if (!inlineAnnotationBody.value.trim() || !props.pendingSelection) return
  await editor.addAnnotation(
    inlineAnnotationBody.value.trim(),
    'inline',
    props.pendingSelection.from,
    props.pendingSelection.to,
    selectedColor.value,
  )
  inlineAnnotationBody.value = ''
  emit('discard-selection')
}

// ── Recherche annotations ─────────────────────────────────────
const annotationSearchInput = ref('')
let annotationSearchTimer: ReturnType<typeof setTimeout> | null = null

function onAnnotationSearch() {
  if (annotationSearchTimer) clearTimeout(annotationSearchTimer)
  annotationSearchTimer = setTimeout(() => editor.searchAnnotations(annotationSearchInput.value), 300)
}

function clearAnnotationSearch() {
  annotationSearchInput.value = ''
  editor.searchAnnotations('')
}

function navigateToAnnotation(ann: Annotation) {
  for (const arc of editor.arcs) {
    for (const ch of arc.chapters ?? []) {
      const s = ch.scenes?.find(s => s.id === ann.scene?.id)
      if (s) { editor.setActiveScene(s); clearAnnotationSearch(); return }
    }
  }
}

// ── Notes ─────────────────────────────────────────────────────
const newNoteOpen = ref(false)
const newNoteBody = ref('')
const editingNoteId = ref<string | null>(null)
const editingNoteBody = ref('')

async function submitNote() {
  if (!newNoteBody.value.trim()) return
  await editor.addNote(newNoteBody.value.trim())
  newNoteBody.value = ''
  newNoteOpen.value = false
}

function startEditNote(note: { id: string; body: string }) {
  editingNoteId.value = note.id
  editingNoteBody.value = note.body
}

async function saveEditNote() {
  if (!editingNoteId.value || !editingNoteBody.value.trim()) return
  await editor.updateNote(editingNoteId.value, editingNoteBody.value.trim())
  editingNoteId.value = null
  editingNoteBody.value = ''
}

// ── Fiches ────────────────────────────────────────────────────
const CARD_TYPES = [
  { value: 'personnage', label: 'Personnage', color: '#3b82f6' },
  { value: 'lieu',       label: 'Lieu',       color: '#10b981' },
  { value: 'evenement',  label: 'Événement',  color: '#f59e0b' },
  { value: 'objet',      label: 'Objet',      color: '#ef4444' },
  { value: 'theme',      label: 'Thème',      color: '#8b5cf6' },
]
const cardsMode = ref<'scene' | 'global'>('scene')
const cardsSearchQuery = ref('')
const showCreateCardForm = ref(false)
const newCardTitle = ref('')
const newCardType = ref('personnage')

const cardDisplay = computed(() => auth.user?.preferences?.cardDisplay ?? 'dot')

function cardTypeColor(type: string) {
  return CARD_TYPES.find(t => t.value === type)?.color ?? '#6b7280'
}
function cardTypeLabel(type: string) {
  return CARD_TYPES.find(t => t.value === type)?.label ?? type
}
function initials(title: string) {
  return title.trim().split(/\s+/).slice(0, 2).map(w => w[0]?.toUpperCase() ?? '').join('')
}

const filteredSceneCards = computed(() => {
  const q = cardsSearchQuery.value.toLowerCase()
  return q ? editor.sceneCards.filter(c => c.title.toLowerCase().includes(q) || c.type.toLowerCase().includes(q)) : editor.sceneCards
})

const filteredProjectCards = computed(() => {
  const q = cardsSearchQuery.value.toLowerCase()
  return q ? editor.projectCards.filter(c => c.title.toLowerCase().includes(q) || c.type.toLowerCase().includes(q)) : editor.projectCards
})

async function submitCreateCard() {
  if (!newCardTitle.value.trim()) return
  const defaultAttrs = (auth.preferences.defaultAttributes?.[newCardType.value] ?? [])
    .map((k: string) => ({ key: k, value: '' }))
  await editor.createProjectCard({
    type: newCardType.value,
    title: newCardTitle.value.trim(),
    ...(defaultAttrs.length ? { attributes: defaultAttrs } : {}),
  })
  newCardTitle.value = ''
  showCreateCardForm.value = false
}

// ── Dépliage fiche inline ─────────────────────────────────────
const expandedCardId  = ref<string | null>(null)
const expandedCards   = ref<Record<string, Card>>({})
const expandingCardId = ref<string | null>(null)

async function toggleCardExpand(cardId: string) {
  if (expandedCardId.value === cardId) {
    expandedCardId.value = null
    return
  }
  expandedCardId.value = cardId
  if (!expandedCards.value[cardId]) {
    expandingCardId.value = cardId
    try {
      expandedCards.value[cardId] = await cardsService.show(cardId)
    } finally {
      expandingCardId.value = null
    }
  }
}

// ── Todos sidebar ─────────────────────────────────────────────

const arcTodos     = ref<Todo[]>([])
const chapterTodos = ref<Todo[]>([])
const todosLoading = ref(false)
const newTodoText  = ref('')
const todosScope   = ref<'chapter' | 'arc'>('chapter')

const currentChapter = computed(() => {
  if (!editor.activeScene) return null
  for (const arc of editor.arcs) {
    const ch = arc.chapters?.find(c => c.scenes?.some(s => s.id === editor.activeScene!.id))
    if (ch) return ch
  }
  return null
})

const currentArc = computed(() => {
  if (!editor.activeScene) return null
  return editor.arcs.find(a => a.chapters?.some(ch => ch.scenes?.some(s => s.id === editor.activeScene!.id))) ?? null
})

async function loadTodosSidebar() {
  if (todosLoading.value) return
  todosLoading.value = true
  try {
    const [arcRes, chRes] = await Promise.all([
      currentArc.value ? todosService.forArc(currentArc.value.id) : Promise.resolve([]),
      currentChapter.value ? todosService.forChapter(currentChapter.value.id) : Promise.resolve([]),
    ])
    arcTodos.value     = arcRes
    chapterTodos.value = chRes
  } finally {
    todosLoading.value = false
  }
}

watch([() => props.tab, () => editor.activeScene?.id], ([newTab]) => {
  if (newTab === 'todos') loadTodosSidebar()
})

const displayedTodos = computed(() => todosScope.value === 'chapter' ? chapterTodos : arcTodos)

async function addSidebarTodo() {
  const text = newTodoText.value.trim()
  if (!text) return
  let todo: Todo
  if (todosScope.value === 'chapter' && currentChapter.value) {
    todo = await todosService.createForChapter(currentChapter.value.id, text)
    chapterTodos.value.push(todo)
  } else if (currentArc.value) {
    todo = await todosService.createForArc(currentArc.value.id, text)
    arcTodos.value.push(todo)
  }
  newTodoText.value = ''
}

async function toggleSidebarTodo(todo: Todo, list: 'arc' | 'chapter') {
  const updated = await todosService.update(todo.id, { is_done: !todo.is_done })
  const arr = list === 'arc' ? arcTodos : chapterTodos
  const idx = arr.value.findIndex(t => t.id === todo.id)
  if (idx !== -1) arr.value[idx] = updated
}

async function deleteSidebarTodo(todo: Todo, list: 'arc' | 'chapter') {
  await todosService.destroy(todo.id)
  if (list === 'arc') arcTodos.value = arcTodos.value.filter(t => t.id !== todo.id)
  else chapterTodos.value = chapterTodos.value.filter(t => t.id !== todo.id)
}

// ── Helpers ───────────────────────────────────────────────────
function relativeTime(dateStr: string) {
  const diff = Date.now() - new Date(dateStr).getTime()
  const mins = Math.floor(diff / 60000)
  if (mins < 1) return "à l'instant"
  if (mins < 60) return `il y a ${mins}min`
  const hours = Math.floor(mins / 60)
  if (hours < 24) return `il y a ${hours}h`
  return `il y a ${Math.floor(hours / 24)}j`
}
</script>

<template>
  <div class="flex flex-col h-full overflow-hidden bg-[#f0efe9] dark:bg-[var(--ui-sidebar-bg)]">

    <!-- Onglets + bouton fermer -->
    <div class="flex items-center border-b border-gray-300 dark:border-gray-700 shrink-0">
      <button
        v-for="t in (['annotations', 'notes', 'fiches', 'todos'] as const)"
        :key="t"
        class="flex-1 py-2.5 text-xs font-medium transition-colors relative"
        :class="tab === t ? 'border-b-2 border-brand-600 text-brand-600' : 'text-gray-400 hover:text-gray-600 dark:hover:text-gray-300'"
        @click="emit('update:tab', t)"
      >
        {{ t === 'annotations' ? 'Annot.' : t === 'notes' ? 'Notes' : t === 'fiches' ? (editor.currentProject?.content_type?.short_name ?? 'Fiches') : 'Todos' }}
        <span
          v-if="t === 'todos' && (arcTodos.filter(x => !x.is_done).length + chapterTodos.filter(x => !x.is_done).length) > 0"
          class="absolute top-1 right-1 w-1.5 h-1.5 rounded-full bg-brand-500"
        />
      </button>

      <button
        class="shrink-0 px-2 py-2.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
        @click="emit('close')"
      >
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
        </svg>
      </button>
    </div>

    <!-- ── Annotations ── -->
    <div v-if="tab === 'annotations'" class="flex-1 flex flex-col overflow-hidden">
      <div class="p-3 border-b border-gray-200 dark:border-gray-700 shrink-0">
        <div class="relative">
          <input
            v-model="annotationSearchInput"
            type="text"
            placeholder="Chercher dans tout le roman…"
            class="w-full text-xs rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200 pl-3 pr-7 py-1.5 focus:outline-none focus:ring-1 focus:ring-brand-500"
            @input="onAnnotationSearch"
          />
          <button v-if="annotationSearchInput" class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 text-xs leading-none" @click="clearAnnotationSearch">✕</button>
        </div>
      </div>

      <!-- Résultats recherche globale -->
      <template v-if="editor.annotationSearchQuery">
        <div class="flex-1 overflow-y-auto p-3 space-y-2">
          <div v-if="editor.annotationSearchLoading" class="text-xs text-gray-400 text-center py-6">Recherche…</div>
          <template v-else>
            <div
              v-for="ann in editor.annotationSearchResults"
              :key="ann.id"
              class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm p-2.5"
              :style="ann.type === 'inline' ? { borderLeftWidth: '3px', borderLeftColor: ann.color } : {}"
            >
              <div class="flex items-start justify-between gap-1 mb-1.5">
                <p class="text-xs text-gray-400 leading-snug truncate">
                  <span v-if="ann.scene?.arc_title">{{ ann.scene.arc_title }} › </span>
                  <span v-if="ann.scene?.chapter_title">{{ ann.scene.chapter_title }} › </span>
                  <span class="font-medium text-gray-500 dark:text-gray-300">{{ ann.scene?.title }}</span>
                </p>
                <button class="shrink-0 text-brand-400 hover:text-brand-600 mt-0.5" @click="navigateToAnnotation(ann)">
                  <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7-7 7M3 12h18"/></svg>
                </button>
              </div>
              <p class="text-xs text-gray-800 dark:text-gray-200 leading-relaxed">{{ ann.body }}</p>
              <div class="flex items-center justify-between mt-1.5">
                <span class="text-xs text-gray-400">{{ ann.user?.name }}</span>
                <span class="text-xs text-gray-400">{{ relativeTime(ann.updated_at) }}</span>
              </div>
            </div>
            <p v-if="!editor.annotationSearchResults.length" class="text-xs text-gray-400 text-center py-6">Aucun résultat.</p>
          </template>
        </div>
      </template>

      <!-- Vue scène active -->
      <template v-else>
        <div v-if="!editor.activeScene" class="flex-1 flex items-center justify-center p-4">
          <p class="text-xs text-gray-400 text-center leading-relaxed">Sélectionne une scène<br>ou cherche dans tout le roman.</p>
        </div>

        <template v-else>
          <!-- Formulaire inline -->
          <div v-if="pendingSelection" class="p-3 border-b border-gray-200 dark:border-gray-700 shrink-0">
            <p class="text-xs font-medium text-gray-600 dark:text-gray-300 mb-1.5">Passage sélectionné</p>
            <p class="text-xs italic text-gray-600 dark:text-gray-400 rounded px-2 py-1 mb-2 line-clamp-2"
               :style="{ background: selectedColor + '22', borderLeft: `3px solid ${selectedColor}` }">
              « {{ pendingSelection.text }} »
            </p>
            <div class="flex items-center gap-1.5 mb-2">
              <span class="text-xs text-gray-400 mr-0.5">Couleur :</span>
              <button v-for="c in ANNOTATION_COLORS" :key="c.value"
                class="w-5 h-5 rounded-full transition-transform border-2"
                :style="{ background: c.value, borderColor: selectedColor === c.value ? c.value : 'transparent', transform: selectedColor === c.value ? 'scale(1.25)' : 'scale(1)' }"
                :title="c.label" @click="selectedColor = c.value" />
            </div>
            <textarea v-model="inlineAnnotationBody" placeholder="Votre annotation…" rows="2" v-focus
              class="w-full text-xs rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200 p-2 resize-none focus:outline-none focus:ring-1 focus:ring-brand-500" />
            <div class="flex gap-2 mt-2">
              <button :disabled="!inlineAnnotationBody.trim()" class="flex-1 text-white text-xs rounded-lg py-1.5 transition-colors disabled:opacity-40" :style="{ background: selectedColor }" @click="submitInlineAnnotation">Annoter</button>
              <button class="text-xs text-gray-400 hover:text-gray-600 px-2" @click="emit('discard-selection'); inlineAnnotationBody = ''">Annuler</button>
            </div>
          </div>

          <!-- Liste annotations -->
          <div class="flex-1 overflow-y-auto p-3 space-y-2">
            <div v-if="editor.panelLoading" class="text-xs text-gray-400 text-center py-6">Chargement…</div>
            <div
              v-for="ann in editor.annotations"
              :id="`ann-${ann.id}`"
              :key="ann.id"
              class="rounded-lg border p-2.5 group relative transition-all duration-300 bg-white dark:bg-gray-800 shadow-sm"
              :class="highlightedAnnotationId === ann.id ? 'ring-2 scale-[1.01]' : 'border-gray-200 dark:border-gray-700'"
              :style="highlightedAnnotationId === ann.id
                ? { borderColor: ann.color, background: ann.color + '15' }
                : ann.type === 'inline' ? { borderLeftWidth: '3px', borderLeftColor: ann.color } : {}"
            >
              <div v-if="ann.type === 'inline' && ann.anchor_start != null" class="flex items-start gap-1.5 mb-1.5">
                <p class="flex-1 text-xs italic rounded px-1.5 py-0.5 line-clamp-2" :style="{ color: ann.color, background: ann.color + '18' }">
                  « {{ getAnchorText(ann) }} »
                </p>
                <button class="shrink-0 opacity-60 hover:opacity-100 mt-0.5" :style="{ color: ann.color }" title="Aller au passage"
                  @click="ann.anchor_start != null && ann.anchor_end != null && emit('focus-annotation', ann.anchor_start, ann.anchor_end)">
                  <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <circle cx="12" cy="12" r="3" stroke-width="2"/>
                    <path stroke-linecap="round" stroke-width="2" d="M12 2v3m0 14v3M2 12h3m14 0h3"/>
                  </svg>
                </button>
              </div>

              <template v-if="editingAnnotationId === ann.id">
                <div class="flex items-center gap-1.5 mb-2">
                  <button v-for="c in ANNOTATION_COLORS" :key="c.value"
                    class="w-4 h-4 rounded-full border-2 transition-transform"
                    :style="{ background: c.value, borderColor: editingAnnotationColor === c.value ? c.value : 'transparent', transform: editingAnnotationColor === c.value ? 'scale(1.25)' : 'scale(1)' }"
                    @click="editingAnnotationColor = c.value" />
                </div>
                <textarea v-model="editingAnnotationBody" rows="3" v-focus
                  class="w-full text-xs rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200 p-1.5 resize-none focus:outline-none focus:ring-1 focus:ring-brand-500" />
                <div class="flex gap-2 mt-1.5">
                  <button :disabled="!editingAnnotationBody.trim()" class="flex-1 text-white text-xs rounded py-1 disabled:opacity-40" :style="{ background: editingAnnotationColor }" @click="saveEditAnnotation">Enregistrer</button>
                  <button class="text-xs text-gray-400 hover:text-gray-600 px-2" @click="editingAnnotationId = null">Annuler</button>
                </div>
              </template>

              <template v-else>
                <p class="text-xs text-gray-800 dark:text-gray-200 leading-relaxed">{{ ann.body }}</p>
                <div class="flex items-center justify-between mt-1.5">
                  <span class="text-xs text-gray-400">{{ ann.user?.name }}</span>
                  <div class="flex items-center gap-2">
                    <span class="text-xs text-gray-400">{{ relativeTime(ann.updated_at) }}</span>
                    <button class="opacity-0 group-hover:opacity-100 text-gray-400 hover:text-brand-500 transition-opacity p-0.5" @click="startEditAnnotation(ann)">
                      <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    </button>
                    <button class="opacity-0 group-hover:opacity-100 text-gray-400 hover:text-red-400 transition-opacity p-0.5 text-sm leading-none" @click="editor.removeAnnotation(ann.id)">✕</button>
                  </div>
                </div>
              </template>
            </div>
            <p v-if="!editor.panelLoading && !editor.annotations.length" class="text-xs text-gray-400 text-center py-6">
              Aucune annotation.<br><span class="text-gray-300 dark:text-gray-600">Sélectionne un passage pour annoter.</span>
            </p>
          </div>

          <!-- Bouton annotation globale -->
          <div class="p-3 border-t border-gray-200 dark:border-gray-800 shrink-0">
            <div v-if="showGlobalForm" class="mb-2">
              <div class="flex items-center gap-1.5 mb-2">
                <span class="text-xs text-gray-400 mr-0.5">Couleur :</span>
                <button v-for="c in ANNOTATION_COLORS" :key="c.value"
                  class="w-5 h-5 rounded-full transition-transform border-2"
                  :style="{ background: c.value, borderColor: selectedColor === c.value ? c.value : 'transparent', transform: selectedColor === c.value ? 'scale(1.25)' : 'scale(1)' }"
                  @click="selectedColor = c.value" />
              </div>
              <textarea v-model="newAnnotationBody" placeholder="Note globale sur la scène…" rows="2" v-focus
                class="w-full text-xs rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200 p-2 resize-none focus:outline-none focus:ring-1 focus:ring-brand-500" />
              <div class="flex gap-2 mt-1.5">
                <button :disabled="!newAnnotationBody.trim()" class="flex-1 text-white text-xs rounded-lg py-1 disabled:opacity-40" :style="{ background: selectedColor }" @click="submitGlobalAnnotation">Ajouter</button>
                <button class="text-xs text-gray-400 hover:text-gray-600 px-2" @click="showGlobalForm = false; newAnnotationBody = ''">Annuler</button>
              </div>
            </div>
            <button v-else class="w-full border border-dashed border-gray-300 dark:border-gray-700 rounded-lg py-1.5 text-xs text-gray-400 hover:text-brand-600 hover:border-brand-400 transition-colors" @click="showGlobalForm = true">+ annotation globale</button>
          </div>
        </template>
      </template>
    </div>

    <!-- ── Notes ── -->
    <div v-else-if="tab === 'notes'" class="flex-1 flex flex-col overflow-hidden">
      <div v-if="newNoteOpen" class="p-3 border-b border-gray-200 dark:border-gray-800 shrink-0">
        <textarea v-model="newNoteBody" placeholder="Votre note…" rows="3" v-focus
          class="w-full text-xs rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200 p-2 resize-none focus:outline-none focus:ring-1 focus:ring-brand-500" />
        <div class="flex gap-2 mt-2">
          <button :disabled="!newNoteBody.trim()" class="flex-1 bg-brand-600 hover:bg-brand-800 disabled:opacity-40 text-white text-xs rounded-lg py-1.5 transition-colors" @click="submitNote">Ajouter</button>
          <button class="text-xs text-gray-400 hover:text-gray-600 px-2" @click="newNoteOpen = false; newNoteBody = ''">Annuler</button>
        </div>
      </div>

      <div class="flex-1 overflow-y-auto p-3 space-y-2">
        <div v-for="note in editor.projectNotes" :key="note.id" class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm p-2.5 group relative">
          <template v-if="editingNoteId === note.id">
            <textarea v-model="editingNoteBody" rows="4" v-focus
              class="w-full text-xs rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200 p-1.5 resize-none focus:outline-none focus:ring-1 focus:ring-brand-500" />
            <div class="flex gap-2 mt-1.5">
              <button :disabled="!editingNoteBody.trim()" class="flex-1 bg-brand-600 hover:bg-brand-800 disabled:opacity-40 text-white text-xs rounded py-1 transition-colors" @click="saveEditNote">Enregistrer</button>
              <button class="text-xs text-gray-400 hover:text-gray-600 px-2" @click="editingNoteId = null">Annuler</button>
            </div>
          </template>
          <template v-else>
            <p class="text-xs text-gray-800 dark:text-gray-200 leading-relaxed whitespace-pre-wrap">{{ note.body }}</p>
            <div class="flex items-center justify-between mt-1.5">
              <span class="text-xs text-gray-400">{{ relativeTime(note.updated_at) }}</span>
              <div class="flex items-center gap-2">
                <button class="opacity-0 group-hover:opacity-100 text-gray-400 hover:text-brand-500 transition-opacity p-0.5" @click="startEditNote(note)">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                </button>
                <button class="opacity-0 group-hover:opacity-100 text-gray-400 hover:text-red-400 transition-opacity p-0.5 text-sm leading-none" @click="editor.removeNote(note.id)">✕</button>
              </div>
            </div>
          </template>
        </div>
        <p v-if="!editor.notesLoading && !editor.projectNotes.length" class="text-xs text-gray-400 text-center py-6">Aucune note.</p>
      </div>

      <div class="p-3 border-t border-gray-200 dark:border-gray-800 shrink-0">
        <button class="w-full border border-dashed border-gray-300 dark:border-gray-700 rounded-lg py-1.5 text-xs text-gray-400 hover:text-brand-600 hover:border-brand-400 transition-colors" @click="newNoteOpen = true">+ note</button>
      </div>
    </div>

    <!-- ── Fiches ── -->
    <div v-else-if="tab === 'fiches'" class="flex-1 flex flex-col overflow-hidden">
      <div class="p-3 border-b border-gray-200 dark:border-gray-700 shrink-0 space-y-2">
        <div class="relative">
          <input v-model="cardsSearchQuery" type="text" placeholder="Chercher une fiche…"
            class="w-full text-xs rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200 pl-3 pr-7 py-1.5 focus:outline-none focus:ring-1 focus:ring-brand-500" />
          <button v-if="cardsSearchQuery" class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 text-xs" @click="cardsSearchQuery = ''">✕</button>
        </div>
        <div class="flex rounded-lg overflow-hidden border border-gray-200 dark:border-gray-700 text-xs">
          <button class="flex-1 py-1 transition-colors" :class="cardsMode === 'scene' ? 'bg-brand-600 text-white' : 'text-gray-500 hover:bg-gray-50 dark:hover:bg-gray-800'" @click="cardsMode = 'scene'">Scène</button>
          <button class="flex-1 py-1 transition-colors border-l border-gray-200 dark:border-gray-700" :class="cardsMode === 'global' ? 'bg-brand-600 text-white' : 'text-gray-500 hover:bg-gray-50 dark:hover:bg-gray-800'" @click="cardsMode = 'global'">{{ editor.currentProject?.content_type?.short_name ?? editor.currentProject?.content_type?.name ?? 'Global' }}</button>
        </div>
      </div>

      <template v-if="cardsMode === 'scene'">
        <div v-if="!editor.activeScene" class="flex-1 flex items-center justify-center p-4">
          <p class="text-xs text-gray-400 text-center leading-relaxed">Sélectionne une scène<br>pour voir les fiches liées.</p>
        </div>
        <div v-else class="flex-1 overflow-y-auto p-3 space-y-1">
          <div v-if="editor.cardsLoading" class="text-xs text-gray-400 text-center py-6">Chargement…</div>
          <template v-else>
            <div
              v-for="card in filteredSceneCards" :key="card.id"
              class="rounded-xl overflow-hidden border transition-all"
              :class="expandedCardId === card.id
                ? 'border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm'
                : 'border-transparent hover:border-gray-200 dark:hover:border-gray-700 hover:bg-white/70 dark:hover:bg-gray-800/40'"
            >
              <div
                class="w-full text-left flex items-center gap-2 px-2.5 py-2 transition-colors group cursor-pointer"
                @click="toggleCardExpand(card.id)"
              >
                <svg class="w-3 h-3 text-gray-400 shrink-0 transition-transform duration-150" :class="expandedCardId === card.id ? 'rotate-90' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
                <!-- Pastille ou avatar -->
                <template v-if="cardDisplay === 'dot'">
                  <span class="w-2 h-2 rounded-full shrink-0" :style="{ background: cardTypeColor(card.type) }" />
                </template>
                <template v-else>
                  <div v-if="card.images?.find((i: any) => i.is_avatar)" class="w-6 h-6 rounded-full overflow-hidden shrink-0" :style="{ outline: '2px solid ' + cardTypeColor(card.type), outlineOffset: '1px' }">
                    <img :src="card.images.find((i: any) => i.is_avatar)!.url" class="w-full h-full object-cover" />
                  </div>
                  <div v-else class="w-6 h-6 rounded-full flex items-center justify-center text-white text-[9px] font-semibold shrink-0" :style="{ background: cardTypeColor(card.type), outline: '2px solid ' + cardTypeColor(card.type), outlineOffset: '1px' }">
                    {{ initials(card.title) }}
                  </div>
                </template>
                <span class="flex-1 text-xs font-medium text-gray-800 dark:text-gray-200 truncate">{{ card.title }}</span>
                <button
                  class="opacity-0 group-hover:opacity-100 transition-opacity p-0.5 rounded hover:bg-gray-100 dark:hover:bg-gray-700 shrink-0"
                  title="Éditer"
                  @click.stop="emit('card-selected', card.id)"
                >
                  <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536M9 13l6.586-6.586a2 2 0 012.828 2.828L11.828 15.828a2 2 0 01-1.414.586H9v-2a2 2 0 01.586-1.414z"/>
                  </svg>
                </button>
              </div>
              <CardInlineView v-if="expandedCardId === card.id" :card="expandedCards[card.id]" :loading="expandingCardId === card.id" :color="cardTypeColor(card.type)" />
            </div>
            <p v-if="!filteredSceneCards.length" class="text-xs text-gray-400 text-center py-6 leading-relaxed">Aucune fiche liée à cette scène<br><span class="text-gray-300 dark:text-gray-600">via les mots-clés.</span></p>
          </template>
        </div>
      </template>

      <template v-else>
        <div class="flex-1 overflow-y-auto p-3 space-y-1">
          <div v-if="editor.cardsLoading" class="text-xs text-gray-400 text-center py-6">Chargement…</div>
          <template v-else>
            <div
              v-for="card in filteredProjectCards" :key="card.id"
              class="rounded-xl overflow-hidden border transition-all"
              :class="expandedCardId === card.id
                ? 'border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm'
                : 'border-transparent hover:border-gray-200 dark:hover:border-gray-700 hover:bg-white/70 dark:hover:bg-gray-800/40'"
            >
              <div
                class="w-full text-left flex items-center gap-2 px-2.5 py-2 transition-colors group cursor-pointer"
                @click="toggleCardExpand(card.id)"
              >
                <svg class="w-3 h-3 text-gray-400 shrink-0 transition-transform duration-150" :class="expandedCardId === card.id ? 'rotate-90' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
                <span class="w-2 h-2 rounded-full shrink-0" :style="{ background: cardTypeColor(card.type) }" />
                <span class="flex-1 text-xs font-medium text-gray-800 dark:text-gray-200 truncate">{{ card.title }}</span>
                <button
                  class="opacity-0 group-hover:opacity-100 transition-opacity p-0.5 rounded hover:bg-gray-100 dark:hover:bg-gray-700 shrink-0"
                  title="Éditer"
                  @click.stop="emit('card-selected', card.id)"
                >
                  <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536M9 13l6.586-6.586a2 2 0 012.828 2.828L11.828 15.828a2 2 0 01-1.414.586H9v-2a2 2 0 01.586-1.414z"/>
                  </svg>
                </button>
              </div>
              <CardInlineView v-if="expandedCardId === card.id" :card="expandedCards[card.id]" :loading="expandingCardId === card.id" :color="cardTypeColor(card.type)" />
            </div>
            <p v-if="!filteredProjectCards.length" class="text-xs text-gray-400 text-center py-6">Aucune fiche.</p>
          </template>
        </div>
        <div class="p-3 border-t border-gray-200 dark:border-gray-800 shrink-0">
          <template v-if="showCreateCardForm">
            <div class="flex gap-1 flex-wrap mb-2">
              <button v-for="t in CARD_TYPES" :key="t.value"
                class="text-xs px-2 py-0.5 rounded-full border transition-colors"
                :style="newCardType === t.value ? { background: t.color, borderColor: t.color, color: 'white' } : { borderColor: t.color, color: t.color }"
                @click="newCardType = t.value">{{ t.label }}</button>
            </div>
            <input v-model="newCardTitle" type="text" placeholder="Titre de la fiche…" v-focus
              class="w-full text-xs rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200 px-2.5 py-1.5 focus:outline-none focus:ring-1 focus:ring-brand-500"
              @keydown.enter="submitCreateCard" @keydown.esc="showCreateCardForm = false; newCardTitle = ''" />
            <div class="flex gap-2 mt-2">
              <button :disabled="!newCardTitle.trim()" class="flex-1 text-white text-xs rounded-lg py-1.5 disabled:opacity-40" :style="{ background: cardTypeColor(newCardType) }" @click="submitCreateCard">Créer</button>
              <button class="text-xs text-gray-400 hover:text-gray-600 px-2" @click="showCreateCardForm = false; newCardTitle = ''">Annuler</button>
            </div>
          </template>
          <button v-else class="w-full border border-dashed border-gray-300 dark:border-gray-700 rounded-lg py-1.5 text-xs text-gray-400 hover:text-brand-600 hover:border-brand-400 transition-colors" @click="showCreateCardForm = true">+ nouvelle fiche</button>
        </div>
      </template>
    </div>

    <!-- ── Todos ── -->
    <div v-if="tab === 'todos'" class="flex-1 flex flex-col overflow-hidden">

      <!-- Sélecteur chapitre / arc -->
      <div class="flex border-b border-gray-100 dark:border-gray-800 shrink-0">
        <button
          class="flex-1 py-2 text-xs font-medium transition-colors"
          :class="todosScope === 'chapter'
            ? 'text-brand-600 dark:text-brand-400 bg-brand-50 dark:bg-brand-900/20'
            : 'text-gray-400 hover:text-gray-600 dark:hover:text-gray-300'"
          @click="todosScope = 'chapter'"
        >
          Chapitre
          <span v-if="chapterTodos.filter(t => !t.is_done).length" class="ml-1 text-[10px] font-semibold text-brand-500">{{ chapterTodos.filter(t => !t.is_done).length }}</span>
        </button>
        <button
          class="flex-1 py-2 text-xs font-medium transition-colors"
          :class="todosScope === 'arc'
            ? 'text-brand-600 dark:text-brand-400 bg-brand-50 dark:bg-brand-900/20'
            : 'text-gray-400 hover:text-gray-600 dark:hover:text-gray-300'"
          @click="todosScope = 'arc'"
        >
          Arc
          <span v-if="arcTodos.filter(t => !t.is_done).length" class="ml-1 text-[10px] font-semibold text-brand-500">{{ arcTodos.filter(t => !t.is_done).length }}</span>
        </button>
      </div>

      <!-- Context label -->
      <div class="px-3 py-1.5 shrink-0 border-b border-gray-100 dark:border-gray-800">
        <p class="text-[10px] text-gray-400 truncate">
          <span v-if="todosScope === 'chapter'">{{ currentChapter?.title ?? '—' }}</span>
          <span v-else>{{ currentArc?.title ?? '—' }}</span>
        </p>
      </div>

      <div v-if="todosLoading" class="flex-1 flex items-center justify-center">
        <svg class="w-4 h-4 animate-spin text-gray-400" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"/>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
        </svg>
      </div>

      <div v-else class="flex-1 overflow-y-auto px-3 py-2 space-y-0.5">
        <p v-if="displayedTodos.value.length === 0" class="text-xs text-gray-400 text-center py-6">
          Aucune todo pour {{ todosScope === 'chapter' ? 'ce chapitre' : 'cet arc' }}.
        </p>

        <!-- Pending -->
        <div
          v-for="todo in displayedTodos.value.filter(t => !t.is_done)"
          :key="todo.id"
          class="group flex items-start gap-2 py-1"
        >
          <button
            class="shrink-0 mt-0.5 w-3.5 h-3.5 rounded border-2 border-gray-300 dark:border-gray-600 hover:border-brand-400 transition-colors"
            @click="toggleSidebarTodo(todo, todosScope)"
          />
          <span class="flex-1 text-xs text-gray-800 dark:text-gray-200 leading-relaxed">{{ todo.text }}</span>
          <button
            class="shrink-0 p-0.5 opacity-0 group-hover:opacity-100 text-gray-400 hover:text-red-500 transition-opacity"
            @click="deleteSidebarTodo(todo, todosScope)"
          >
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
          </button>
        </div>

        <!-- Done -->
        <template v-if="displayedTodos.value.some(t => t.is_done)">
          <div class="pt-2 border-t border-gray-100 dark:border-gray-800 mt-1">
            <p class="text-[10px] uppercase tracking-widest text-gray-400 mb-1">Terminées</p>
          </div>
          <div
            v-for="todo in displayedTodos.value.filter(t => t.is_done)"
            :key="todo.id"
            class="group flex items-start gap-2 py-0.5"
          >
            <button
              class="shrink-0 mt-0.5 w-3.5 h-3.5 rounded border-2 border-brand-400 bg-brand-400 flex items-center justify-center"
              @click="toggleSidebarTodo(todo, todosScope)"
            >
              <svg class="w-2 h-2 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
              </svg>
            </button>
            <span class="flex-1 text-xs text-gray-400 dark:text-gray-500 line-through leading-relaxed">{{ todo.text }}</span>
            <button
              class="shrink-0 p-0.5 opacity-0 group-hover:opacity-100 text-gray-400 hover:text-red-500 transition-opacity"
              @click="deleteSidebarTodo(todo, todosScope)"
            >
              <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
              </svg>
            </button>
          </div>
        </template>
      </div>

      <!-- Nouvelle todo -->
      <div class="px-3 py-2 border-t border-gray-200 dark:border-gray-800 shrink-0">
        <div class="flex gap-1.5">
          <input
            v-model="newTodoText"
            type="text"
            placeholder="Nouvelle todo…"
            class="flex-1 text-xs rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-gray-100 px-2.5 py-1.5 focus:outline-none focus:ring-1 focus:ring-brand-500"
            @keyup.enter="addSidebarTodo"
          />
          <button
            class="px-2.5 py-1.5 rounded-lg bg-brand-600 hover:bg-brand-700 text-white text-xs font-medium transition-colors disabled:opacity-50"
            :disabled="!newTodoText.trim()"
            @click="addSidebarTodo"
          >+</button>
        </div>
      </div>
    </div>

  </div>
</template>
