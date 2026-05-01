<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import Dialog from 'primevue/dialog'
import { useCardsStore } from '@/stores/cards.store'
import { notesService } from '@/services/notes.service'
import type { Note } from '@/types'

const props = defineProps<{
  cardId: string | null
  projectId: string
}>()

const emit = defineEmits<{
  close: []
  'navigate-to-scene': [sceneId: string]
}>()

const visible = ref(false)
watch(() => props.cardId, (id) => { visible.value = id !== null })
watch(visible, (v) => { if (!v) emit('close') })

const cards = useCardsStore()
const loading = ref(false)

// ── Tabs ──────────────────────────────────────────────────────
const activeTab = ref<'attributs' | 'liaisons' | 'referentiel'>('attributs')

// ── Titre éditable ────────────────────────────────────────────
const editingTitle = ref(false)
const editTitleValue = ref('')

function startEditTitle() {
  if (!cards.activeCard) return
  editTitleValue.value = cards.activeCard.title
  editingTitle.value = true
}

async function saveTitle() {
  if (!editTitleValue.value.trim()) return
  await cards.updateCard({ title: editTitleValue.value.trim() })
  editingTitle.value = false
}

// ── Attributs ─────────────────────────────────────────────────
const editMode = ref(false)
const editedAttrs = ref<{ key: string; value: string }[]>([])

function startEdit() {
  editedAttrs.value = (cards.activeCard?.attributes ?? []).map(a => ({
    key: a.key,
    value: String(a.value ?? ''),
  }))
  editMode.value = true
}

function addAttr() { editedAttrs.value.push({ key: '', value: '' }) }
function removeAttr(i: number) { editedAttrs.value.splice(i, 1) }

async function saveAttrs() {
  if (!cards.activeCard) return
  await cards.updateAttributes(cards.activeCard.id, editedAttrs.value.filter(a => a.key.trim()))
  editMode.value = false
}

// ── Mots-clés ─────────────────────────────────────────────────
const newKeyword = ref('')
const newKeywordCase = ref(false)
const showKwForm = ref(false)

async function addKeyword() {
  if (!newKeyword.value.trim()) return
  await cards.addKeyword(newKeyword.value.trim(), newKeywordCase.value)
  newKeyword.value = ''
  newKeywordCase.value = false
  showKwForm.value = false
}

// ── Référentiel ───────────────────────────────────────────────
async function switchToReferentiel() {
  activeTab.value = 'referentiel'
  await cards.loadOccurrences()
}

function goToScene(sceneId: string) {
  emit('navigate-to-scene', sceneId)
  emit('close')
}

// ── Liaisons ──────────────────────────────────────────────────
const linkSearch = ref('')
const linkSearchResults = ref<import('@/types').Card[]>([])
const linkSearchLoading = ref(false)
const selectedLinkCard = ref<import('@/types').Card | null>(null)
const linkLabel = ref('')
const showLinkForm = ref(false)
let linkSearchTimer: ReturnType<typeof setTimeout> | null = null

async function onLinkSearch() {
  if (linkSearchTimer) clearTimeout(linkSearchTimer)
  if (!linkSearch.value.trim()) { linkSearchResults.value = []; return }
  linkSearchTimer = setTimeout(async () => {
    linkSearchLoading.value = true
    try {
      const res = await (await import('@/services/cards.service')).cardsService.index(props.projectId, { q: linkSearch.value, per_page: 20 })
      linkSearchResults.value = res.data.filter(c => c.id !== cards.activeCard?.id)
    } finally {
      linkSearchLoading.value = false
    }
  }, 300)
}

function selectLinkCard(card: import('@/types').Card) {
  selectedLinkCard.value = card
  linkSearch.value = card.title
  linkSearchResults.value = []
}

async function submitLink() {
  if (!selectedLinkCard.value) return
  await cards.addLink(selectedLinkCard.value.id, linkLabel.value.trim() || null)
  resetLinkForm()
}

function resetLinkForm() {
  showLinkForm.value = false
  linkSearch.value = ''
  linkLabel.value = ''
  selectedLinkCard.value = null
  linkSearchResults.value = []
}

// ── Notes ─────────────────────────────────────────────────────
const cardNotes = ref<Note[]>([])
const newNoteBody = ref('')
const showNoteForm = ref(false)

watch(() => cards.activeCard?.id, async (id) => {
  cardNotes.value = []
  if (!id) return
  const res = await notesService.indexForCard(id)
  cardNotes.value = res.data
})

async function addCardNote() {
  if (!newNoteBody.value.trim() || !cards.activeCard) return
  const note = await notesService.storeForCard(cards.activeCard.id, { body: newNoteBody.value.trim() })
  cardNotes.value.unshift(note)
  newNoteBody.value = ''
  showNoteForm.value = false
}

async function removeCardNote(id: string) {
  await notesService.destroy(id)
  cardNotes.value = cardNotes.value.filter(n => n.id !== id)
}

const editingNoteId = ref<string | null>(null)
const editingNoteBody = ref('')

function startEditNote(note: { id: string; body: string }) {
  editingNoteId.value = note.id
  editingNoteBody.value = note.body
}

async function saveEditNote() {
  if (!editingNoteId.value || !editingNoteBody.value.trim()) return
  const updated = await notesService.update(editingNoteId.value, { body: editingNoteBody.value.trim() })
  const idx = cardNotes.value.findIndex(n => n.id === editingNoteId.value)
  if (idx !== -1) cardNotes.value[idx] = updated
  editingNoteId.value = null
  editingNoteBody.value = ''
}

// ── Chargement ────────────────────────────────────────────────
watch(() => props.cardId, async (id) => {
  if (!id) return
  loading.value = true
  activeTab.value = 'attributs'
  editMode.value = false
  editingTitle.value = false
  showKwForm.value = false
  showNoteForm.value = false
  try {
    await cards.loadCard(id)
  } finally {
    loading.value = false
  }
}, { immediate: true })

// ── Types ─────────────────────────────────────────────────────
const TYPES = [
  { key: 'personnage', label: 'Personnage', bg: 'bg-blue-500',    color: '#3b82f6' },
  { key: 'lieu',       label: 'Lieu',       bg: 'bg-emerald-500', color: '#10b981' },
  { key: 'evenement',  label: 'Événement',  bg: 'bg-amber-500',   color: '#f59e0b' },
  { key: 'objet',      label: 'Objet',      bg: 'bg-red-400',     color: '#ef4444' },
  { key: 'theme',      label: 'Thème',      bg: 'bg-violet-400',  color: '#8b5cf6' },
]

function typeConfig(key: string) {
  return TYPES.find(t => t.key === key) ?? { key, label: key, bg: 'bg-gray-400', color: '#6b7280' }
}

function initials(name: string) {
  return name.split(' ').slice(0, 2).map(n => n[0]).join('').toUpperCase()
}

function relativeTime(dateStr: string) {
  const diff = Date.now() - new Date(dateStr).getTime()
  const min = Math.floor(diff / 60000)
  if (min < 1) return "à l'instant"
  if (min < 60) return `il y a ${min}min`
  const h = Math.floor(min / 60)
  if (h < 24) return `il y a ${h}h`
  return `il y a ${Math.floor(h / 24)}j`
}

// ── Passthrough Dialog ────────────────────────────────────────
const isMaximized = ref(false)

const dialogPt = computed(() => ({
  mask: {
    class: [
      'fixed inset-0 z-50 bg-black/40 backdrop-blur-sm flex items-center justify-center',
      isMaximized.value ? '' : 'p-4',
    ].join(' '),
  },
  root: {
    class: [
      'flex flex-col bg-white dark:bg-gray-900 shadow-2xl overflow-hidden w-full',
      isMaximized.value
        ? 'h-dvh max-w-none rounded-none'
        : 'h-[78vh] max-w-3xl rounded-xl',
    ].join(' '),
  },
  header: {
    class: 'flex items-center gap-3 px-6 py-4 border-b border-gray-200 dark:border-gray-700 shrink-0',
  },
  title: { class: 'flex-1 min-w-0' },
  headerActions: { class: 'flex items-center gap-1 shrink-0' },
  content: { class: 'flex-1 overflow-y-auto p-0 min-h-0' },
  pcMaximizeButton: {
    root: {
      class: 'flex items-center justify-center w-7 h-7 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors focus:outline-none',
    },
  },
  pcCloseButton: {
    root: {
      class: 'flex items-center justify-center w-7 h-7 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors focus:outline-none',
    },
  },
}))
</script>

<template>
  <Dialog
    v-model:visible="visible"
    v-model:maximized="isMaximized"
    maximizable
    modal
    :pt="dialogPt"
  >
    <!-- En-tête : avatar + titre + type -->
    <template #header>
      <div
        class="w-9 h-9 rounded-full flex items-center justify-center text-white text-sm font-semibold shrink-0"
        :class="cards.activeCard ? typeConfig(cards.activeCard.type).bg : 'bg-gray-300'"
      >{{ cards.activeCard ? initials(cards.activeCard.title) : '…' }}</div>

      <div class="flex-1 min-w-0">
        <template v-if="editingTitle">
          <input
            v-model="editTitleValue"
            type="text"
            class="text-base font-semibold text-gray-900 dark:text-gray-100 bg-transparent border-b
                   border-brand-400 focus:outline-none w-full"
            @keydown.enter="saveTitle"
            @keydown.esc="editingTitle = false"
            @blur="saveTitle"
          />
        </template>
        <template v-else>
          <button
            class="text-left group w-full"
            @click="startEditTitle"
          >
            <h2 class="text-base font-semibold text-gray-900 dark:text-gray-100 leading-tight truncate
                       group-hover:text-brand-600 dark:group-hover:text-brand-400 transition-colors">
              {{ cards.activeCard?.title ?? '…' }}
            </h2>
          </button>
        </template>
        <p class="text-xs text-gray-400 mt-0.5">
          {{ cards.activeCard ? typeConfig(cards.activeCard.type).label : '' }}
        </p>
      </div>
    </template>

    <!-- Corps -->
    <div v-if="loading" class="flex items-center justify-center py-20">
      <p class="text-sm text-gray-400">Chargement…</p>
    </div>

    <div v-else-if="cards.activeCard" class="flex flex-col h-full">

      <!-- Tabs -->
      <div class="flex items-center gap-1 px-6 border-b border-gray-200 dark:border-gray-700 shrink-0">
        <button
          v-for="{ key, label } in [
            { key: 'attributs',   label: 'Attributs'   },
            { key: 'liaisons',    label: 'Liaisons'    },
            { key: 'referentiel', label: 'Référentiel' },
          ]"
          :key="key"
          class="py-3 px-1 mr-3 text-sm border-b-2 transition-colors -mb-px"
          :class="activeTab === key
            ? 'border-brand-500 text-brand-600 dark:text-brand-400 font-medium'
            : 'border-transparent text-gray-500 hover:text-gray-700 dark:hover:text-gray-300'"
          @click="key === 'referentiel' ? switchToReferentiel() : (activeTab = key as typeof activeTab, editMode = false)"
        >{{ label }}</button>

        <div class="flex-1" />

        <button
          v-if="activeTab === 'attributs' && !editMode"
          class="text-xs px-3 py-1.5 my-2 border border-gray-300 dark:border-gray-600
                 text-gray-600 dark:text-gray-300 rounded-lg hover:bg-gray-50
                 dark:hover:bg-gray-800 transition-colors"
          @click="startEdit"
        >Modifier</button>
      </div>

      <!-- Contenu scrollable -->
      <div class="flex-1 overflow-y-auto">

        <!-- ── Attributs ── -->
        <div v-if="activeTab === 'attributs'" class="p-6 space-y-6">

          <!-- Lecture -->
          <template v-if="!editMode">
            <div v-if="!cards.activeCard.attributes?.length" class="text-sm text-gray-400 py-2">
              Aucun attribut. Clique sur Modifier pour en ajouter.
            </div>
            <dl v-else class="grid grid-cols-2 gap-x-8 gap-y-4">
              <div v-for="attr in cards.activeCard.attributes" :key="attr.id">
                <dt class="text-xs font-semibold uppercase tracking-wide text-gray-400 dark:text-gray-500 mb-0.5">
                  {{ attr.key }}
                </dt>
                <dd class="text-sm text-gray-800 dark:text-gray-200 whitespace-pre-wrap">{{ attr.value }}</dd>
              </div>
            </dl>

            <!-- Mots-clés -->
            <div>
              <p class="text-xs font-semibold uppercase tracking-wide text-gray-400 dark:text-gray-500 mb-2">
                Mots-clés
              </p>
              <div class="flex flex-wrap gap-2">
                <span
                  v-for="kw in cards.activeCard.keywords"
                  :key="kw.id"
                  class="flex items-center gap-1 text-sm px-3 py-1 rounded-full
                         bg-brand-50 dark:bg-brand-900/30 text-brand-700 dark:text-brand-300
                         border border-brand-200 dark:border-brand-700"
                >
                  {{ kw.keyword }}
                  <button
                    class="ml-1 text-brand-400 hover:text-brand-700 leading-none"
                    @click="cards.removeKeyword(kw.id)"
                  >&times;</button>
                </span>

                <template v-if="showKwForm">
                  <input
                    v-model="newKeyword"
                    type="text"
                    placeholder="mot-clé"
                    autofocus
                    class="text-sm px-2 py-1 rounded-full border border-brand-300
                           focus:outline-none focus:ring-1 focus:ring-brand-500 w-28"
                    @keyup.enter="addKeyword"
                    @keyup.escape="showKwForm = false"
                  />
                  <label class="flex items-center gap-1 text-xs text-gray-500 cursor-pointer">
                    <input v-model="newKeywordCase" type="checkbox" class="rounded" />
                    Casse
                  </label>
                  <button
                    class="text-sm px-3 py-1 rounded-full bg-brand-600 text-white hover:bg-brand-800 transition-colors"
                    @click="addKeyword"
                  >OK</button>
                </template>
                <button
                  v-else
                  class="text-sm px-3 py-1 rounded-full border border-dashed
                         border-gray-300 dark:border-gray-600 text-gray-400
                         hover:border-brand-300 hover:text-brand-600 transition-colors"
                  @click="showKwForm = true"
                >+ mot-clé</button>
              </div>
            </div>
          </template>

          <!-- Édition -->
          <template v-else>
            <div class="space-y-2">
              <div v-for="(attr, i) in editedAttrs" :key="i" class="flex gap-2 items-start">
                <input
                  v-model="attr.key"
                  type="text"
                  placeholder="Clé (ex: âge)"
                  class="w-36 shrink-0 text-xs rounded-lg border border-gray-300 dark:border-gray-600
                         bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300
                         px-2.5 py-2 focus:outline-none focus:ring-1 focus:ring-brand-500"
                />
                <textarea
                  v-model="attr.value"
                  rows="2"
                  placeholder="Valeur"
                  class="flex-1 text-sm rounded-lg border border-gray-300 dark:border-gray-600
                         bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300
                         px-2.5 py-2 focus:outline-none focus:ring-1 focus:ring-brand-500 resize-none"
                />
                <button
                  class="mt-1.5 text-gray-400 hover:text-red-500 transition-colors shrink-0"
                  @click="removeAttr(i)"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                  </svg>
                </button>
              </div>
              <button class="text-sm text-brand-600 dark:text-brand-400 hover:underline" @click="addAttr">
                + attribut
              </button>
            </div>
            <div class="flex gap-2 pt-2">
              <button
                class="px-4 py-1.5 text-sm bg-brand-600 hover:bg-brand-800 text-white rounded-lg transition-colors"
                @click="saveAttrs"
              >Enregistrer</button>
              <button
                class="px-4 py-1.5 text-sm text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 transition-colors"
                @click="editMode = false"
              >Annuler</button>
            </div>
          </template>

          <!-- Notes de la fiche -->
          <div class="border-t border-gray-200 dark:border-gray-700 pt-5">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-400 dark:text-gray-500 mb-3">
              Notes
            </p>
            <div class="space-y-2 mb-3">
              <div
                v-for="note in cardNotes"
                :key="note.id"
                class="group rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800"
              >
                <!-- Mode édition -->
                <template v-if="editingNoteId === note.id">
                  <div class="p-3">
                    <textarea
                      v-model="editingNoteBody"
                      rows="4"
                      autofocus
                      class="w-full text-sm rounded-lg border border-gray-300 dark:border-gray-600
                             bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-200
                             p-2 resize-none focus:outline-none focus:ring-1 focus:ring-brand-500"
                    />
                    <div class="flex gap-2 mt-2">
                      <button
                        :disabled="!editingNoteBody.trim()"
                        class="flex-1 bg-brand-600 hover:bg-brand-800 disabled:opacity-40 text-white text-sm rounded-lg py-1.5 transition-colors"
                        @click="saveEditNote"
                      >Enregistrer</button>
                      <button
                        class="text-sm text-gray-400 hover:text-gray-600 px-3"
                        @click="editingNoteId = null"
                      >Annuler</button>
                    </div>
                  </div>
                </template>

                <!-- Mode lecture -->
                <template v-else>
                  <div class="flex gap-2 p-3">
                    <p class="flex-1 text-sm text-gray-700 dark:text-gray-300 leading-relaxed whitespace-pre-wrap">
                      {{ note.body }}
                    </p>
                    <div class="flex flex-col items-end gap-1.5 shrink-0">
                      <span class="text-xs text-gray-400">{{ relativeTime(note.updated_at) }}</span>
                      <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                        <button
                          class="text-gray-400 hover:text-brand-500 transition-colors p-0.5"
                          title="Modifier"
                          @click="startEditNote(note)"
                        >
                          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                          </svg>
                        </button>
                        <button
                          class="text-gray-400 hover:text-red-400 transition-colors p-0.5"
                          title="Supprimer"
                          @click="removeCardNote(note.id)"
                        >
                          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                          </svg>
                        </button>
                      </div>
                    </div>
                  </div>
                </template>
              </div>
              <p v-if="!cardNotes.length" class="text-sm text-gray-400">Aucune note.</p>
            </div>

            <template v-if="showNoteForm">
              <textarea
                v-model="newNoteBody"
                rows="3"
                placeholder="Nouvelle note…"
                autofocus
                class="w-full text-sm rounded-lg border border-gray-300 dark:border-gray-700
                       bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200
                       p-2.5 resize-none focus:outline-none focus:ring-1 focus:ring-brand-500"
              />
              <div class="flex gap-2 mt-2">
                <button
                  :disabled="!newNoteBody.trim()"
                  class="flex-1 bg-brand-600 hover:bg-brand-800 disabled:opacity-40 text-white text-sm rounded-lg py-1.5 transition-colors"
                  @click="addCardNote"
                >Ajouter</button>
                <button
                  class="text-sm text-gray-400 hover:text-gray-600 px-2"
                  @click="showNoteForm = false; newNoteBody = ''"
                >Annuler</button>
              </div>
            </template>
            <button
              v-else
              class="w-full border border-dashed border-gray-300 dark:border-gray-700 rounded-lg
                     py-1.5 text-sm text-gray-400 hover:text-brand-600 hover:border-brand-400
                     transition-colors"
              @click="showNoteForm = true"
            >+ note</button>
          </div>
        </div>

        <!-- ── Liaisons ── -->
        <div v-else-if="activeTab === 'liaisons'" class="p-6 space-y-4">

          <!-- Liste existante -->
          <div v-if="cards.activeCard.links?.length" class="space-y-2">
            <div
              v-for="link in cards.activeCard.links"
              :key="link.id"
              class="flex items-center gap-3 p-3 rounded-lg border border-gray-200 dark:border-gray-700
                     bg-white dark:bg-gray-800 group"
            >
              <div
                class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-medium text-white shrink-0"
                :class="typeConfig(link.linked_card.type).bg"
              >{{ initials(link.linked_card.title) }}</div>
              <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-800 dark:text-gray-100">{{ link.linked_card.title }}</p>
                <p class="text-xs text-gray-400">
                  {{ typeConfig(link.linked_card.type).label }}
                  <template v-if="link.label"> · <em>{{ link.label }}</em></template>
                </p>
              </div>
              <button
                class="opacity-0 group-hover:opacity-100 text-gray-300 hover:text-red-400 transition-opacity shrink-0"
                title="Supprimer la liaison"
                @click="cards.removeLink(link.id)"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
              </button>
            </div>
          </div>
          <p v-else class="text-sm text-gray-400">Aucune liaison définie.</p>

          <!-- Formulaire d'ajout -->
          <div v-if="showLinkForm" class="rounded-xl border border-gray-200 dark:border-gray-700 p-4 space-y-3">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Nouvelle liaison</p>

            <!-- Recherche de fiche -->
            <div class="relative">
              <input
                v-model="linkSearch"
                type="text"
                placeholder="Chercher une fiche…"
                autofocus
                class="w-full text-sm rounded-lg border border-gray-300 dark:border-gray-600
                       bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200
                       px-3 py-2 focus:outline-none focus:ring-1 focus:ring-brand-500"
                @input="onLinkSearch"
              />
              <div
                v-if="linkSearchResults.length"
                class="absolute top-full left-0 right-0 mt-1 bg-white dark:bg-gray-800
                       border border-gray-200 dark:border-gray-700 rounded-lg shadow-lg z-10
                       max-h-48 overflow-y-auto"
              >
                <button
                  v-for="card in linkSearchResults"
                  :key="card.id"
                  class="w-full flex items-center gap-2 px-3 py-2 text-left hover:bg-gray-50
                         dark:hover:bg-gray-700 transition-colors"
                  @click="selectLinkCard(card)"
                >
                  <span
                    class="w-2 h-2 rounded-full shrink-0"
                    :style="{ background: typeConfig(card.type).color }"
                  />
                  <span class="text-sm text-gray-800 dark:text-gray-200 flex-1 truncate">{{ card.title }}</span>
                  <span class="text-xs text-gray-400 shrink-0">{{ typeConfig(card.type).label }}</span>
                </button>
              </div>
            </div>

            <!-- Label optionnel -->
            <input
              v-model="linkLabel"
              type="text"
              placeholder="Label (ex: ami, ennemi, mentor)…"
              class="w-full text-sm rounded-lg border border-gray-300 dark:border-gray-600
                     bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200
                     px-3 py-2 focus:outline-none focus:ring-1 focus:ring-brand-500"
            />

            <div class="flex gap-2">
              <button
                :disabled="!selectedLinkCard"
                class="flex-1 bg-brand-600 hover:bg-brand-800 disabled:opacity-40 text-white
                       text-sm rounded-lg py-2 transition-colors"
                @click="submitLink"
              >Ajouter</button>
              <button
                class="text-sm text-gray-400 hover:text-gray-600 px-3"
                @click="resetLinkForm"
              >Annuler</button>
            </div>
          </div>

          <button
            v-else
            class="w-full border border-dashed border-gray-300 dark:border-gray-700 rounded-lg
                   py-2 text-sm text-gray-400 hover:text-brand-600 hover:border-brand-400 transition-colors"
            @click="showLinkForm = true"
          >+ nouvelle liaison</button>
        </div>

        <!-- ── Référentiel ── -->
        <div v-else-if="activeTab === 'referentiel'" class="p-6">
          <div class="flex items-center justify-between mb-4">
            <p class="text-sm text-gray-500 dark:text-gray-400">
              {{ cards.occurrences.length }} occurrence{{ cards.occurrences.length !== 1 ? 's' : '' }} dans le manuscrit
            </p>
            <button
              class="text-xs px-3 py-1.5 rounded-lg border border-gray-300 dark:border-gray-600
                     text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800
                     transition-colors disabled:opacity-50"
              :disabled="cards.rebuildStatus === 'pending'"
              @click="cards.rebuildIndex(projectId)"
            >{{ cards.rebuildStatus === 'pending' ? 'Indexation…' : 'Reconstruire l\'index' }}</button>
          </div>

          <div v-if="!cards.occurrences.length" class="text-sm text-gray-400 py-2">
            Aucune occurrence. Lance une reconstruction de l'index.
          </div>
          <div v-else class="space-y-2">
            <div
              v-for="occ in cards.occurrences"
              :key="occ.id"
              class="p-3 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800"
            >
              <p class="text-sm text-gray-700 dark:text-gray-300 italic leading-relaxed">"{{ occ.context_excerpt }}"</p>
              <div class="flex items-center justify-between mt-2">
                <div>
                  <p v-if="occ.scene" class="text-xs font-medium text-gray-500 dark:text-gray-400">
                    {{ occ.scene.title }}
                  </p>
                  <p class="text-xs text-gray-400">pos. {{ occ.position_start }}–{{ occ.position_end }}</p>
                </div>
                <button
                  v-if="occ.scene"
                  class="flex items-center gap-1 text-xs text-brand-600 dark:text-brand-400
                         hover:text-brand-800 dark:hover:text-brand-300 transition-colors shrink-0"
                  title="Aller à la scène"
                  @click="goToScene(occ.scene.id)"
                >
                  <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7-7 7M3 12h18"/>
                  </svg>
                  Aller à la scène
                </button>
              </div>
            </div>
          </div>
        </div>

      </div>
    </div>
  </Dialog>
</template>

