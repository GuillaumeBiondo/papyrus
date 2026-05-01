<script setup lang="ts">
import { computed, ref } from 'vue'
import { useEditorStore } from '@/stores/editor.store'
import type { Annotation } from '@/types'

const props = defineProps<{
  tab: 'annotations' | 'notes' | 'fiches'
  pendingSelection: { from: number; to: number; text: string } | null
  highlightedAnnotationId: string | null
  getAnchorText: (ann: { anchor_start: number | null; anchor_end: number | null }) => string
}>()

const emit = defineEmits<{
  'update:tab': [tab: 'annotations' | 'notes' | 'fiches']
  close: []
  'card-selected': [id: string]
  'focus-annotation': [anchor_start: number, anchor_end: number]
  'discard-selection': []
}>()

const vFocus = { mounted: (el: HTMLElement) => el.focus() }
const editor = useEditorStore()

// ── Couleurs annotations ───────────────────────────────────────
const ANNOTATION_COLORS = [
  { value: '#f59e0b', label: 'Ambre' },
  { value: '#ef4444', label: 'Rouge' },
  { value: '#10b981', label: 'Vert' },
  { value: '#3b82f6', label: 'Bleu' },
  { value: '#8b5cf6', label: 'Violet' },
  { value: '#ec4899', label: 'Rose' },
]
const selectedColor = ref(ANNOTATION_COLORS[0].value)

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

function cardTypeColor(type: string) {
  return CARD_TYPES.find(t => t.value === type)?.color ?? '#6b7280'
}
function cardTypeLabel(type: string) {
  return CARD_TYPES.find(t => t.value === type)?.label ?? type
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
  await editor.createProjectCard({ type: newCardType.value, title: newCardTitle.value.trim() })
  newCardTitle.value = ''
  showCreateCardForm.value = false
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
  <div class="flex flex-col h-full overflow-hidden bg-white dark:bg-gray-900">

    <!-- Onglets + bouton fermer -->
    <div class="flex items-center border-b border-gray-300 dark:border-gray-700 shrink-0">
      <button
        v-for="t in (['annotations', 'notes', 'fiches'] as const)"
        :key="t"
        class="flex-1 py-2.5 text-xs font-medium transition-colors"
        :class="tab === t ? 'border-b-2 border-brand-600 text-brand-600' : 'text-gray-400 hover:text-gray-600 dark:hover:text-gray-300'"
        @click="emit('update:tab', t)"
      >{{ t.charAt(0).toUpperCase() + t.slice(1) }}</button>

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
              class="rounded-lg border border-gray-200 dark:border-gray-700 p-2.5"
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
              class="rounded-lg border p-2.5 group relative transition-all duration-300"
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
        <div v-for="note in editor.projectNotes" :key="note.id" class="rounded-lg border border-gray-200 dark:border-gray-700 p-2.5 group relative">
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
    <div v-else class="flex-1 flex flex-col overflow-hidden">
      <div class="p-3 border-b border-gray-200 dark:border-gray-700 shrink-0 space-y-2">
        <div class="relative">
          <input v-model="cardsSearchQuery" type="text" placeholder="Chercher une fiche…"
            class="w-full text-xs rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200 pl-3 pr-7 py-1.5 focus:outline-none focus:ring-1 focus:ring-brand-500" />
          <button v-if="cardsSearchQuery" class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 text-xs" @click="cardsSearchQuery = ''">✕</button>
        </div>
        <div class="flex rounded-lg overflow-hidden border border-gray-200 dark:border-gray-700 text-xs">
          <button class="flex-1 py-1 transition-colors" :class="cardsMode === 'scene' ? 'bg-brand-600 text-white' : 'text-gray-500 hover:bg-gray-50 dark:hover:bg-gray-800'" @click="cardsMode = 'scene'">Scène</button>
          <button class="flex-1 py-1 transition-colors border-l border-gray-200 dark:border-gray-700" :class="cardsMode === 'global' ? 'bg-brand-600 text-white' : 'text-gray-500 hover:bg-gray-50 dark:hover:bg-gray-800'" @click="cardsMode = 'global'">Global</button>
        </div>
      </div>

      <template v-if="cardsMode === 'scene'">
        <div v-if="!editor.activeScene" class="flex-1 flex items-center justify-center p-4">
          <p class="text-xs text-gray-400 text-center leading-relaxed">Sélectionne une scène<br>pour voir les fiches liées.</p>
        </div>
        <div v-else class="flex-1 overflow-y-auto p-3 space-y-1">
          <div v-if="editor.cardsLoading" class="text-xs text-gray-400 text-center py-6">Chargement…</div>
          <template v-else>
            <button v-for="card in filteredSceneCards" :key="card.id"
              class="w-full text-left flex items-center gap-2 px-2 py-1.5 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors group"
              @click="emit('card-selected', card.id)">
              <span class="w-2 h-2 rounded-full shrink-0" :style="{ background: cardTypeColor(card.type) }" />
              <span class="flex-1 text-xs text-gray-800 dark:text-gray-200 truncate">{{ card.title }}</span>
              <span class="text-xs opacity-0 group-hover:opacity-60 transition-opacity shrink-0" :style="{ color: cardTypeColor(card.type) }">{{ cardTypeLabel(card.type) }}</span>
            </button>
            <p v-if="!filteredSceneCards.length" class="text-xs text-gray-400 text-center py-6 leading-relaxed">Aucune fiche liée à cette scène<br><span class="text-gray-300 dark:text-gray-600">via les mots-clés.</span></p>
          </template>
        </div>
      </template>

      <template v-else>
        <div class="flex-1 overflow-y-auto p-3 space-y-1">
          <div v-if="editor.cardsLoading" class="text-xs text-gray-400 text-center py-6">Chargement…</div>
          <template v-else>
            <button v-for="card in filteredProjectCards" :key="card.id"
              class="w-full text-left flex items-center gap-2 px-2 py-1.5 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors group"
              @click="emit('card-selected', card.id)">
              <span class="w-2 h-2 rounded-full shrink-0" :style="{ background: cardTypeColor(card.type) }" />
              <span class="flex-1 text-xs text-gray-800 dark:text-gray-200 truncate">{{ card.title }}</span>
              <span class="text-xs opacity-0 group-hover:opacity-60 transition-opacity shrink-0" :style="{ color: cardTypeColor(card.type) }">{{ cardTypeLabel(card.type) }}</span>
            </button>
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

  </div>
</template>
