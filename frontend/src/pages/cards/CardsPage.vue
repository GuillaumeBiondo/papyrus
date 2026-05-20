<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useCardsStore } from '@/stores/cards.store'
import { useAuthStore } from '@/stores/auth.store'
import { notesService } from '@/services/notes.service'
import type { CardAttribute, Note } from '@/types'

const route  = useRoute()
const router = useRouter()
const cards  = useCardsStore()
const auth   = useAuthStore()

const projectId = route.params.projectId as string

// ── Affichage liste ────────────────────────────────────────
const cardDisplay = computed(() => auth.user?.preferences?.cardDisplay ?? 'dot')

// ── Types ─────────────────────────────────────────────────
const TYPES: { key: string; label: string; color: string; dot: string; hex: string; bg: string }[] = [
  { key: 'personnage', label: 'Personnages', color: 'bg-brand-50 text-brand-600 dark:bg-brand-800/30 dark:text-brand-300 border border-brand-200 dark:border-brand-700', dot: 'bg-brand-500',   hex: '#6D5FE6', bg: 'bg-brand-500'   },
  { key: 'lieu',       label: 'Lieux',       color: 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-700', dot: 'bg-emerald-500', hex: '#10b981', bg: 'bg-emerald-500' },
  { key: 'evenement',  label: 'Événements',  color: 'bg-amber-50 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300 border border-amber-200 dark:border-amber-700', dot: 'bg-amber-500',   hex: '#f59e0b', bg: 'bg-amber-500'   },
  { key: 'objet',      label: 'Objets',      color: 'bg-red-50 text-red-700 dark:bg-red-900/30 dark:text-red-300 border border-red-200 dark:border-red-700', dot: 'bg-red-400',     hex: '#ef4444', bg: 'bg-red-400'     },
  { key: 'theme',      label: 'Thèmes',      color: 'bg-violet-50 text-violet-700 dark:bg-violet-900/30 dark:text-violet-300 border border-violet-200 dark:border-violet-700', dot: 'bg-violet-400',  hex: '#8b5cf6', bg: 'bg-violet-400'  },
]

function typeConfig(key: string) {
  return TYPES.find(t => t.key === key) ?? { key, label: key, color: 'bg-gray-100 text-gray-600 border border-gray-200', dot: 'bg-gray-400', hex: '#9ca3af', bg: 'bg-gray-400' }
}

// ── Filtres ───────────────────────────────────────────────
const activeFilter = ref<string | null>(null)
const search = ref('')

const presentTypes = computed(() => {
  const keys = new Set(cards.cards.map(c => c.type))
  return TYPES.filter(t => keys.has(t.key))
})

const filteredCards = computed(() => {
  let list = cards.cards
  if (activeFilter.value) list = list.filter(c => c.type === activeFilter.value)
  if (search.value.trim()) {
    const q = search.value.toLowerCase()
    list = list.filter(c => c.title.toLowerCase().includes(q))
  }
  return list
})

// ── Sélection ─────────────────────────────────────────────
const activeTab = ref<'attributs' | 'liaisons' | 'lore' | 'referentiel'>('attributs')

async function selectCard(card: typeof cards.cards[0]) {
  activeTab.value = 'attributs'
  editMode.value = false
  confirmDelete.value = false
  expandedLinkId.value = null
  cancelEditLink()
  resetLinkForm()
  await cards.loadCard(card.id)
}

// ── Suppression ───────────────────────────────────────────
const confirmDelete = ref(false)
const deleting = ref(false)

async function deleteActiveCard() {
  if (!cards.activeCard) return
  deleting.value = true
  try {
    await cards.deleteCard(cards.activeCard.id)
    confirmDelete.value = false
  } finally {
    deleting.value = false
  }
}

// ── Édition attributs ─────────────────────────────────────
const editMode = ref(false)
const editedAttrs = ref<{ key: string; value: string }[]>([])

function startEdit() {
  editedAttrs.value = (cards.activeCard?.attributes ?? []).map(a => ({
    key: a.key,
    value: String(a.value ?? ''),
  }))
  editMode.value = true
}

function addAttr() {
  editedAttrs.value.push({ key: '', value: '' })
}

function removeAttr(i: number) {
  editedAttrs.value.splice(i, 1)
}

async function saveAttrs() {
  if (!cards.activeCard) return
  await cards.updateAttributes(
    cards.activeCard.id,
    editedAttrs.value.filter(a => a.key.trim()),
  )
  editMode.value = false
}

// ── Mots-clés ─────────────────────────────────────────────
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

// ── Référentiel ───────────────────────────────────────────
async function switchToReferentiel() {
  activeTab.value = 'referentiel'
  await cards.loadOccurrences()
}

// ── Notes de fiche ────────────────────────────────────────
const cardNotes = ref<Note[]>([])
const newNoteBody = ref('')
const editingNoteId = ref<string | null>(null)
const editingNoteBody = ref('')
const showNoteForm = ref(false)
const integratingNoteId = ref<string | null>(null)

watch(() => cards.activeCard?.id, async (id) => {
  cardNotes.value = []
  editingNoteId.value = null
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

async function integrateNoteIntoLore(noteId: string) {
  integratingNoteId.value = noteId
  try {
    const newLore = await cards.integrateLoreNote(noteId)
    loreText.value = newLore
    cardNotes.value = cardNotes.value.filter(n => n.id !== noteId)
    activeTab.value = 'lore'
  } finally {
    integratingNoteId.value = null
  }
}

// ── Lore ──────────────────────────────────────────────────
const loreText = ref('')
const loreSaving = ref(false)
let loreSaveTimer: ReturnType<typeof setTimeout> | null = null

watch(() => cards.activeCard?.lore, (val) => {
  loreText.value = val ?? ''
}, { immediate: true })

function onLoreInput() {
  if (loreSaveTimer) clearTimeout(loreSaveTimer)
  loreSaveTimer = setTimeout(saveLore, 1500)
}

async function saveLore() {
  if (!cards.activeCard) return
  loreSaving.value = true
  try {
    await cards.updateCard({ lore: loreText.value })
  } finally {
    loreSaving.value = false
  }
}

async function saveLoreNow() {
  if (loreSaveTimer) { clearTimeout(loreSaveTimer); loreSaveTimer = null }
  await saveLore()
}

// ── Liaisons ──────────────────────────────────────────────
const expandedLinkId = ref<string | null>(null)
const editingLinkId = ref<string | null>(null)
const editingLinkLabel = ref('')
const editingLinkDescription = ref('')
const savingLinkId = ref<string | null>(null)
const showLinkForm = ref(false)
const linkSearch = ref('')
const linkSearchResults = ref<typeof cards.cards>([])
const linkSearchLoading = ref(false)
const selectedLinkCard = ref<typeof cards.cards[0] | null>(null)
const linkLabel = ref('')
let linkSearchTimer: ReturnType<typeof setTimeout> | null = null

async function onLinkSearch() {
  if (linkSearchTimer) clearTimeout(linkSearchTimer)
  if (!linkSearch.value.trim()) { linkSearchResults.value = []; return }
  linkSearchTimer = setTimeout(async () => {
    linkSearchLoading.value = true
    try {
      const { cardsService } = await import('@/services/cards.service')
      const res = await cardsService.index(projectId, { q: linkSearch.value, per_page: 20 })
      linkSearchResults.value = res.data.filter(c => c.id !== cards.activeCard?.id)
    } finally {
      linkSearchLoading.value = false
    }
  }, 300)
}

function selectLinkCard(card: typeof cards.cards[0]) {
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

function expandLink(linkId: string) {
  expandedLinkId.value = expandedLinkId.value === linkId ? null : linkId
  if (expandedLinkId.value !== linkId) cancelEditLink()
}

function startEditLink(link: { id: string; label: string | null; description: string | null }) {
  editingLinkId.value = link.id
  editingLinkLabel.value = link.label ?? ''
  editingLinkDescription.value = link.description ?? ''
}

function cancelEditLink() {
  editingLinkId.value = null
  editingLinkLabel.value = ''
  editingLinkDescription.value = ''
}

async function saveEditLink(linkId: string) {
  savingLinkId.value = linkId
  try {
    await cards.updateLink(linkId, {
      label: editingLinkLabel.value.trim() || null,
      description: editingLinkDescription.value.trim() || null,
    })
    cancelEditLink()
  } finally {
    savingLinkId.value = null
  }
}

// ── Création ──────────────────────────────────────────────
const showCreateModal = ref(false)
const newTitle = ref('')
const newType = ref('personnage')
const creating = ref(false)

async function createCard() {
  if (!newTitle.value.trim()) return
  creating.value = true
  try {
    const defaults = (auth.preferences.defaultAttributes?.[newType.value] ?? [])
      .map((k: string) => ({ key: k, value: '' }))
    await cards.createCard(projectId, {
      title: newTitle.value.trim(),
      type: newType.value,
      ...(defaults.length ? { attributes: defaults as any } : {}),
    })
    showCreateModal.value = false
    newTitle.value = ''
  } finally {
    creating.value = false
  }
}

// ── Helpers visuels ───────────────────────────────────────
function initials(name: string) {
  return name.split(' ').map(n => n[0]).join('').slice(0, 2).toUpperCase()
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

onMounted(() => cards.fetchForProject(projectId))
</script>

<template>
  <div class="flex h-full overflow-hidden">

    <!-- ── Sidebar gauche ─────────────────────────────── -->
    <aside class="w-52 shrink-0 flex flex-col border-r border-gray-300 dark:border-gray-700
                  bg-[#f0efe9] dark:bg-gray-900 overflow-hidden">

      <!-- Retour éditeur -->
      <div class="px-3 pt-3 pb-2">
        <button
          class="flex items-center gap-1 text-xs text-gray-500 hover:text-gray-700
                 dark:hover:text-gray-300 transition-colors"
          @click="router.push({ name: 'editor', params: { projectId } })"
        >
          <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
          </svg>
          retour à l'éditeur
        </button>
        <p class="mt-2 text-sm font-semibold text-gray-800 dark:text-gray-100">Fiches du projet</p>
      </div>

      <!-- Filtres type -->
      <div class="px-3 pb-2 flex flex-wrap gap-1.5">
        <button
          v-for="t in presentTypes"
          :key="t.key"
          class="text-xs px-2 py-0.5 rounded-full transition-colors"
          :class="activeFilter === t.key ? t.color : 'bg-gray-100 dark:bg-gray-800 text-gray-500 dark:text-gray-400 border border-transparent'"
          @click="activeFilter = activeFilter === t.key ? null : t.key"
        >{{ t.label }}</button>
      </div>

      <!-- Recherche -->
      <div class="px-3 pb-2">
        <input
          v-model="search"
          type="text"
          placeholder="Rechercher…"
          class="w-full text-xs rounded-md border border-gray-300 dark:border-gray-700
                 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300
                 px-2 py-1 focus:outline-none focus:ring-1 focus:ring-brand-500"
        />
      </div>

      <!-- Liste fiches -->
      <div class="flex-1 overflow-y-auto">
        <div v-if="cards.loading" class="text-xs text-gray-400 text-center py-8">Chargement…</div>
        <div v-else-if="!filteredCards.length" class="text-xs text-gray-400 text-center py-8 px-3">
          Aucune fiche
        </div>
        <button
          v-for="card in filteredCards"
          :key="card.id"
          class="w-full flex items-center gap-2 px-3 py-2 text-left transition-colors"
          :class="cards.activeCard?.id === card.id
            ? 'bg-white dark:bg-gray-800 border-r-2 border-brand-500'
            : 'hover:bg-gray-100 dark:hover:bg-gray-800'"
          @click="selectCard(card)"
        >
          <!-- Mode pastille -->
          <span
            v-if="cardDisplay === 'dot'"
            class="w-2 h-2 rounded-full shrink-0"
            :class="typeConfig(card.type).dot"
          />
          <!-- Mode avatar -->
          <template v-else>
            <div
              v-if="card.images?.find(i => i.is_avatar)"
              class="w-7 h-7 rounded-full overflow-hidden shrink-0"
              :style="{ outline: '2px solid ' + typeConfig(card.type).hex, outlineOffset: '1px' }"
            >
              <img
                :src="card.images.find(i => i.is_avatar)!.url"
                class="w-full h-full object-cover"
              />
            </div>
            <div
              v-else
              class="w-7 h-7 rounded-full flex items-center justify-center text-white text-[10px] font-semibold shrink-0"
              :class="typeConfig(card.type).bg"
              :style="{ outline: '2px solid ' + typeConfig(card.type).hex, outlineOffset: '1px' }"
            >{{ initials(card.title) }}</div>
          </template>

          <div class="min-w-0">
            <p class="text-sm text-gray-800 dark:text-gray-100 truncate leading-tight">
              {{ card.title }}
            </p>
            <p class="text-xs text-gray-400 truncate">{{ typeConfig(card.type).label.replace(/s$/, '') }}</p>
          </div>
        </button>
      </div>

      <!-- Bouton nouvelle fiche -->
      <div class="p-3 border-t border-gray-300 dark:border-gray-700">
        <button
          class="w-full text-xs text-gray-500 hover:text-brand-600 dark:hover:text-brand-400
                 border border-dashed border-gray-300 dark:border-gray-600
                 hover:border-brand-300 dark:hover:border-brand-600
                 rounded-lg py-2 transition-colors"
          @click="showCreateModal = true"
        >+ nouvelle fiche</button>
      </div>
    </aside>

    <!-- ── Panneau détail ──────────────────────────────── -->
    <div class="flex-1 overflow-y-auto">

      <!-- Placeholder vide -->
      <div
        v-if="!cards.activeCard"
        class="h-full flex flex-col items-center justify-center gap-3 text-gray-400"
      >
        <svg class="w-10 h-10 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586
                   a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
        </svg>
        <p class="text-sm">Sélectionne une fiche</p>
      </div>

      <!-- Détail fiche -->
      <div v-else class="p-6 max-w-3xl">

        <!-- Header -->
        <div class="flex items-start gap-4 mb-5">
          <div
            class="w-12 h-12 rounded-full flex items-center justify-center
                   text-white text-base font-semibold shrink-0"
            :class="{
              'bg-brand-500': cards.activeCard.type === 'personnage',
              'bg-emerald-500': cards.activeCard.type === 'lieu',
              'bg-amber-500': cards.activeCard.type === 'evenement',
              'bg-red-400': cards.activeCard.type === 'objet',
              'bg-violet-400': cards.activeCard.type === 'theme',
              'bg-gray-400': !['personnage','lieu','evenement','objet','theme'].includes(cards.activeCard.type),
            }"
          >{{ initials(cards.activeCard.title) }}</div>
          <div class="flex-1 min-w-0">
            <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-100 leading-tight">
              {{ cards.activeCard.title }}
            </h1>
            <p class="text-sm text-gray-500 mt-0.5">
              {{ typeConfig(cards.activeCard.type).label.replace(/s$/, '') }}
              <template v-if="cards.activeCard.attributes?.find(a => a.key === 'rôle') || cards.activeCard.attributes?.find(a => a.key === 'role')">
                · {{ cards.activeCard.attributes?.find(a => a.key === 'rôle' || a.key === 'role')?.value }}
              </template>
            </p>
          </div>
          <div class="flex items-center gap-2 shrink-0">
            <template v-if="confirmDelete">
              <span class="text-xs text-gray-500">Supprimer définitivement ?</span>
              <button
                class="text-xs px-2.5 py-1.5 rounded-lg bg-red-500 hover:bg-red-600 text-white transition-colors disabled:opacity-50"
                :disabled="deleting"
                @click="deleteActiveCard"
              >{{ deleting ? '…' : 'Confirmer' }}</button>
              <button
                class="text-xs px-2.5 py-1.5 rounded-lg text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
                @click="confirmDelete = false"
              >Annuler</button>
            </template>
            <template v-else>
              <button
                class="p-1.5 rounded-lg text-red-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors"
                title="Supprimer cette fiche"
                @click="confirmDelete = true"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
              </button>
              <button
                v-if="activeTab === 'attributs' && !editMode"
                class="text-sm px-3 py-1.5 border border-gray-300 dark:border-gray-600
                       text-gray-600 dark:text-gray-300 rounded-lg hover:bg-gray-50
                       dark:hover:bg-gray-800 transition-colors"
                @click="startEdit"
              >Modifier</button>
            </template>
          </div>
        </div>

        <!-- Tabs -->
        <div class="flex gap-1 border-b border-gray-200 dark:border-gray-700 mb-5 overflow-x-auto">
          <button
            class="pb-2 px-1 text-sm border-b-2 transition-colors whitespace-nowrap"
            :class="activeTab === 'attributs'
              ? 'border-brand-500 text-brand-600 dark:text-brand-400 font-medium'
              : 'border-transparent text-gray-500 hover:text-gray-700 dark:hover:text-gray-300'"
            @click="activeTab = 'attributs'; editMode = false"
          >Attributs</button>
          <button
            class="pb-2 px-1 ml-4 text-sm border-b-2 transition-colors whitespace-nowrap"
            :class="activeTab === 'liaisons'
              ? 'border-brand-500 text-brand-600 dark:text-brand-400 font-medium'
              : 'border-transparent text-gray-500 hover:text-gray-700 dark:hover:text-gray-300'"
            @click="activeTab = 'liaisons'; editMode = false"
          >Liaisons</button>
          <button
            class="pb-2 px-1 ml-4 text-sm border-b-2 transition-colors whitespace-nowrap"
            :class="activeTab === 'lore'
              ? 'border-brand-500 text-brand-600 dark:text-brand-400 font-medium'
              : 'border-transparent text-gray-500 hover:text-gray-700 dark:hover:text-gray-300'"
            @click="activeTab = 'lore'; editMode = false"
          >Lore</button>
          <button
            class="pb-2 px-1 ml-4 text-sm border-b-2 transition-colors whitespace-nowrap"
            :class="activeTab === 'referentiel'
              ? 'border-brand-500 text-brand-600 dark:text-brand-400 font-medium'
              : 'border-transparent text-gray-500 hover:text-gray-700 dark:hover:text-gray-300'"
            @click="switchToReferentiel"
          >Référentiel</button>
        </div>

        <!-- ── Tab Attributs ─────────────────────────── -->
        <div v-if="activeTab === 'attributs'">

          <!-- Mode lecture -->
          <template v-if="!editMode">
            <div
              v-if="!cards.activeCard.attributes?.length"
              class="text-sm text-gray-400 py-4"
            >Aucun attribut. Clique sur Modifier pour en ajouter.</div>

            <dl v-else class="grid grid-cols-2 gap-x-8 gap-y-4">
              <div v-for="attr in cards.activeCard.attributes" :key="attr.id">
                <dt class="text-xs font-semibold uppercase tracking-wide text-gray-400 dark:text-gray-500 mb-0.5">
                  {{ attr.key }}
                </dt>
                <dd class="text-sm text-gray-800 dark:text-gray-200 whitespace-pre-wrap">
                  {{ attr.value }}
                </dd>
              </div>
            </dl>

            <!-- Mots-clés -->
            <div class="mt-6">
              <p class="text-xs font-semibold uppercase tracking-wide text-gray-400 dark:text-gray-500 mb-2">
                Mots-clés pour le référentiel
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

                <!-- Formulaire inline ajout mot-clé -->
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

            <!-- Notes -->
            <div class="mt-8 border-t border-gray-200 dark:border-gray-700 pt-5">
              <p class="text-xs font-semibold uppercase tracking-wide text-gray-400 dark:text-gray-500 mb-3">
                Notes
              </p>
              <div class="space-y-2 mb-3">
                <div
                  v-for="note in cardNotes"
                  :key="note.id"
                  class="rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50"
                >
                  <!-- Mode édition note -->
                  <div v-if="editingNoteId === note.id" class="p-3">
                    <textarea
                      v-model="editingNoteBody"
                      rows="4"
                      class="w-full text-sm rounded-lg border border-gray-300 dark:border-gray-600
                             bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200
                             px-3 py-2 focus:outline-none focus:ring-1 focus:ring-brand-500 resize-none"
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

                  <!-- Mode lecture note -->
                  <div v-else class="p-3">
                    <p class="text-sm text-gray-700 dark:text-gray-300 leading-relaxed whitespace-pre-wrap mb-2">
                      {{ note.body }}
                    </p>
                    <div class="flex items-center justify-between gap-2">
                      <span class="text-xs text-gray-400">{{ relativeTime(note.updated_at) }}</span>
                      <div class="flex items-center gap-1">
                        <button
                          class="flex items-center gap-1 text-xs text-brand-500 hover:text-brand-700 dark:hover:text-brand-300 transition-colors px-2 py-1 rounded-md hover:bg-brand-50 dark:hover:bg-brand-900/20 disabled:opacity-40"
                          :disabled="integratingNoteId === note.id"
                          title="Intégrer au lore via IA"
                          @click="integrateNoteIntoLore(note.id)"
                        >
                          <svg v-if="integratingNoteId !== note.id" class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3l14 9-14 9V3z"/>
                          </svg>
                          <svg v-else class="w-3.5 h-3.5 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 12a8 8 0 018-8v8H4z"/>
                          </svg>
                          {{ integratingNoteId === note.id ? '…' : 'Lore' }}
                        </button>
                        <button
                          class="text-gray-400 hover:text-brand-500 transition-colors p-1.5 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700"
                          title="Modifier"
                          @click="startEditNote(note)"
                        >
                          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                          </svg>
                        </button>
                        <button
                          class="text-gray-400 hover:text-red-400 transition-colors p-1.5 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700"
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
                </div>
                <p v-if="!cardNotes.length" class="text-sm text-gray-400">Aucune note.</p>
              </div>

              <template v-if="showNoteForm">
                <textarea
                  v-model="newNoteBody"
                  rows="3"
                  placeholder="Nouvelle note…"
                  autofocus
                  class="w-full text-sm rounded-lg border border-gray-300 dark:border-gray-600
                         bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300
                         px-3 py-2 focus:outline-none focus:ring-1 focus:ring-brand-500 resize-none"
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
          </template>

          <!-- Mode édition -->
          <template v-else>
            <div class="space-y-3">
              <div
                v-for="(attr, i) in editedAttrs"
                :key="i"
                class="flex gap-2"
              >
                <input
                  v-model="attr.key"
                  type="text"
                  placeholder="Clé (ex: âge)"
                  class="w-32 shrink-0 text-xs rounded-md border border-gray-300 dark:border-gray-600
                         bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300
                         px-2 py-1.5 focus:outline-none focus:ring-1 focus:ring-brand-500"
                />
                <textarea
                  v-model="attr.value"
                  rows="1"
                  placeholder="Valeur"
                  class="flex-1 text-sm rounded-md border border-gray-300 dark:border-gray-600
                         bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300
                         px-2 py-1.5 focus:outline-none focus:ring-1 focus:ring-brand-500 resize-none"
                />
                <button
                  class="text-gray-400 hover:text-red-500 transition-colors shrink-0"
                  @click="removeAttr(i)"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                  </svg>
                </button>
              </div>

              <button
                class="text-sm text-brand-600 dark:text-brand-400 hover:underline"
                @click="addAttr"
              >+ attribut</button>
            </div>

            <div class="flex gap-2 mt-4">
              <button
                class="px-4 py-1.5 text-sm bg-brand-600 hover:bg-brand-800 text-white
                       rounded-lg transition-colors"
                @click="saveAttrs"
              >Enregistrer</button>
              <button
                class="px-4 py-1.5 text-sm text-gray-500 hover:text-gray-700
                       dark:hover:text-gray-300 transition-colors"
                @click="editMode = false"
              >Annuler</button>
            </div>
          </template>
        </div>

        <!-- ── Tab Liaisons ──────────────────────────── -->
        <div v-else-if="activeTab === 'liaisons'">
          <div
            v-if="!cards.activeCard.links?.length"
            class="text-sm text-gray-400 py-4"
          >Aucune liaison définie.</div>
          <div v-else class="space-y-2 mb-4">
            <div
              v-for="link in cards.activeCard.links"
              :key="link.id"
              class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 overflow-hidden"
            >
              <!-- En-tête -->
              <div class="flex items-center gap-3 p-3">
                <div
                  class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-medium text-white shrink-0"
                  :class="{
                    'bg-brand-500': link.linked_card.type === 'personnage',
                    'bg-emerald-500': link.linked_card.type === 'lieu',
                    'bg-amber-500': link.linked_card.type === 'evenement',
                    'bg-gray-400': !['personnage','lieu','evenement'].includes(link.linked_card.type),
                  }"
                >{{ initials(link.linked_card.title) }}</div>
                <div class="flex-1 min-w-0">
                  <p class="text-sm font-medium text-gray-800 dark:text-gray-100">
                    {{ link.linked_card.title }}
                  </p>
                  <p class="text-xs text-gray-400">
                    {{ typeConfig(link.linked_card.type).label.replace(/s$/, '') }}
                    <template v-if="link.label"> · <em>{{ link.label }}</em></template>
                  </p>
                </div>
                <div class="flex items-center gap-1 shrink-0">
                  <button
                    class="p-1.5 rounded-md text-gray-400 hover:text-brand-500 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                    :title="expandedLinkId === link.id ? 'Masquer' : 'Développer / modifier'"
                    @click="expandLink(link.id)"
                  >
                    <svg
                      class="w-3.5 h-3.5 transition-transform"
                      :class="expandedLinkId === link.id ? 'rotate-180' : ''"
                      fill="none" stroke="currentColor" viewBox="0 0 24 24"
                    >
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                  </button>
                  <button
                    class="p-1.5 rounded-md text-gray-300 hover:text-red-400 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                    title="Supprimer la liaison"
                    @click="cards.removeLink(link.id)"
                  >
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                  </button>
                </div>
              </div>

              <!-- Zone description -->
              <div v-if="expandedLinkId === link.id" class="border-t border-gray-100 dark:border-gray-700 px-3 pb-3 pt-2 bg-gray-50 dark:bg-gray-800/50">
                <template v-if="editingLinkId === link.id">
                  <div class="space-y-2">
                    <input
                      v-model="editingLinkLabel"
                      type="text"
                      placeholder="Relation (ami, ennemi, mentor…)"
                      class="w-full text-sm rounded-lg border border-gray-300 dark:border-gray-600
                             bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200
                             px-3 py-1.5 focus:outline-none focus:ring-1 focus:ring-brand-500"
                    />
                    <textarea
                      v-model="editingLinkDescription"
                      rows="4"
                      placeholder="Décris cette relation, son histoire, son évolution…"
                      class="w-full text-sm rounded-lg border border-gray-300 dark:border-gray-600
                             bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200
                             px-3 py-2 focus:outline-none focus:ring-1 focus:ring-brand-500 resize-none"
                    />
                    <div class="flex gap-2">
                      <button
                        :disabled="savingLinkId === link.id"
                        class="flex-1 bg-brand-600 hover:bg-brand-800 disabled:opacity-40 text-white text-sm rounded-lg py-1.5 transition-colors"
                        @click="saveEditLink(link.id)"
                      >{{ savingLinkId === link.id ? '…' : 'Enregistrer' }}</button>
                      <button
                        class="text-sm text-gray-400 hover:text-gray-600 px-3"
                        @click="cancelEditLink"
                      >Annuler</button>
                    </div>
                  </div>
                </template>
                <template v-else>
                  <p
                    v-if="link.description"
                    class="text-sm text-gray-600 dark:text-gray-300 leading-relaxed whitespace-pre-wrap mb-2"
                  >{{ link.description }}</p>
                  <p v-else class="text-sm text-gray-400 italic mb-2">Aucune description.</p>
                  <button
                    class="text-xs text-brand-600 dark:text-brand-400 hover:underline"
                    @click="startEditLink(link)"
                  >Modifier</button>
                </template>
              </div>
            </div>
          </div>

          <!-- Formulaire nouvelle liaison -->
          <div v-if="showLinkForm" class="rounded-xl border border-gray-200 dark:border-gray-700 p-4 space-y-3">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Nouvelle liaison</p>
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
                  <span class="w-2 h-2 rounded-full shrink-0" :class="typeConfig(card.type).dot" />
                  <span class="text-sm text-gray-800 dark:text-gray-200 flex-1 truncate">{{ card.title }}</span>
                  <span class="text-xs text-gray-400 shrink-0">{{ typeConfig(card.type).label.replace(/s$/, '') }}</span>
                </button>
              </div>
            </div>
            <input
              v-model="linkLabel"
              type="text"
              placeholder="Relation (ami, ennemi, mentor)…"
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

        <!-- ── Tab Lore ───────────────────────────────── -->
        <div v-else-if="activeTab === 'lore'" class="flex flex-col gap-3">
          <div class="flex items-center justify-between">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-400 dark:text-gray-500">
              Lore — biographie &amp; histoire
            </p>
            <span v-if="loreSaving" class="text-xs text-gray-400 animate-pulse">Sauvegarde…</span>
          </div>
          <textarea
            v-model="loreText"
            placeholder="Écris ici tout ce que tu sais sur ce personnage : son histoire, sa psychologie, ses secrets, ses contradictions…"
            class="w-full text-sm rounded-xl border border-gray-200 dark:border-gray-700
                   bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200
                   px-4 py-3 focus:outline-none focus:ring-2 focus:ring-brand-400
                   resize-none leading-relaxed"
            style="min-height: 400px"
            @input="onLoreInput"
            @blur="saveLoreNow"
          />
          <p class="text-xs text-gray-400">
            Sauvegarde automatique. Tu peux aussi intégrer des notes depuis l'onglet Attributs.
          </p>
        </div>

        <!-- ── Tab Référentiel ───────────────────────── -->
        <div v-else-if="activeTab === 'referentiel'">
          <div class="flex items-center justify-between mb-4">
            <p class="text-sm text-gray-500 dark:text-gray-400">
              {{ cards.occurrences.length }} occurrence{{ cards.occurrences.length !== 1 ? 's' : '' }}
              dans le manuscrit
            </p>
            <button
              class="text-xs px-3 py-1.5 rounded-lg border border-gray-300 dark:border-gray-600
                     text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800
                     transition-colors disabled:opacity-50"
              :disabled="cards.rebuildStatus === 'pending'"
              @click="cards.rebuildIndex(projectId)"
            >
              {{ cards.rebuildStatus === 'pending' ? 'Indexation…' : 'Reconstruire l\'index' }}
            </button>
          </div>

          <div v-if="!cards.occurrences.length" class="text-sm text-gray-400 py-4">
            Aucune occurrence trouvée. Lance une reconstruction de l'index.
          </div>
          <div v-else class="space-y-2">
            <div
              v-for="occ in cards.occurrences"
              :key="occ.id"
              class="p-3 rounded-lg border border-gray-200 dark:border-gray-700
                     bg-white dark:bg-gray-800"
            >
              <p class="text-sm text-gray-700 dark:text-gray-300 italic">
                "{{ occ.context_excerpt }}"
              </p>
              <p class="text-xs text-gray-400 mt-1">pos. {{ occ.position_start }}–{{ occ.position_end }}</p>
            </div>
          </div>
        </div>

      </div>
    </div>

    <!-- ── Modal création ─────────────────────────────── -->
    <Transition name="fade">
      <div
        v-if="showCreateModal"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/30 backdrop-blur-sm"
        @click.self="showCreateModal = false"
      >
        <div class="w-full max-w-sm bg-white dark:bg-gray-900 rounded-2xl shadow-xl p-6 mx-4">
          <h2 class="text-base font-semibold text-gray-900 dark:text-gray-100 mb-4">
            Nouvelle fiche
          </h2>

          <div class="space-y-3">
            <div>
              <label class="block text-xs font-medium text-gray-500 mb-1">Type</label>
              <div class="flex flex-wrap gap-2">
                <button
                  v-for="t in TYPES"
                  :key="t.key"
                  class="text-xs px-2.5 py-1 rounded-full transition-colors"
                  :class="newType === t.key ? t.color : 'bg-gray-100 dark:bg-gray-800 text-gray-500 border border-transparent'"
                  @click="newType = t.key"
                >{{ t.label }}</button>
              </div>
            </div>

            <div>
              <label class="block text-xs font-medium text-gray-500 mb-1">Titre *</label>
              <input
                v-model="newTitle"
                type="text"
                placeholder="Aldric, Forêt de Brume…"
                autofocus
                class="w-full rounded-lg border border-gray-300 dark:border-gray-700
                       bg-white dark:bg-gray-800 text-sm text-gray-900 dark:text-gray-100
                       px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-500"
                @keyup.enter="createCard"
                @keyup.escape="showCreateModal = false"
              />
            </div>
          </div>

          <div class="flex gap-2 mt-5">
            <button
              :disabled="creating || !newTitle.trim()"
              class="flex-1 bg-brand-600 hover:bg-brand-800 disabled:opacity-50
                     text-white text-sm font-medium rounded-lg py-2 transition-colors"
              @click="createCard"
            >{{ creating ? 'Création…' : 'Créer' }}</button>
            <button
              class="px-4 text-sm text-gray-500 hover:text-gray-700 dark:hover:text-gray-300"
              @click="showCreateModal = false"
            >Annuler</button>
          </div>
        </div>
      </div>
    </Transition>

  </div>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.15s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
