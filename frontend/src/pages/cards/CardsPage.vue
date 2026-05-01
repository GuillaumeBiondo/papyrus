<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useCardsStore } from '@/stores/cards.store'
import { notesService } from '@/services/notes.service'
import type { CardAttribute, Note } from '@/types'

const route = useRoute()
const router = useRouter()
const cards = useCardsStore()

const projectId = route.params.projectId as string

// ── Types ─────────────────────────────────────────────────
const TYPES: { key: string; label: string; color: string; dot: string }[] = [
  { key: 'personnage', label: 'Personnages', color: 'bg-brand-50 text-brand-600 dark:bg-brand-800/30 dark:text-brand-300 border border-brand-200 dark:border-brand-700', dot: 'bg-brand-500' },
  { key: 'lieu',       label: 'Lieux',       color: 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-700', dot: 'bg-emerald-500' },
  { key: 'evenement',  label: 'Événements',  color: 'bg-amber-50 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300 border border-amber-200 dark:border-amber-700', dot: 'bg-amber-500' },
  { key: 'objet',      label: 'Objets',      color: 'bg-red-50 text-red-700 dark:bg-red-900/30 dark:text-red-300 border border-red-200 dark:border-red-700', dot: 'bg-red-400' },
  { key: 'theme',      label: 'Thèmes',      color: 'bg-violet-50 text-violet-700 dark:bg-violet-900/30 dark:text-violet-300 border border-violet-200 dark:border-violet-700', dot: 'bg-violet-400' },
]

function typeConfig(key: string) {
  return TYPES.find(t => t.key === key) ?? { key, label: key, color: 'bg-gray-100 text-gray-600 border border-gray-200', dot: 'bg-gray-400' }
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
const activeTab = ref<'attributs' | 'liaisons' | 'referentiel'>('attributs')

async function selectCard(card: typeof cards.cards[0]) {
  activeTab.value = 'attributs'
  editMode.value = false
  await cards.loadCard(card.id)
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

watch(() => cards.activeCard?.id, async (id) => {
  if (!id) return
  const res = await notesService.indexForCard(id)
  cardNotes.value = res.data
})

async function addCardNote() {
  if (!newNoteBody.value.trim() || !cards.activeCard) return
  const note = await notesService.storeForCard(cards.activeCard.id, { body: newNoteBody.value.trim() })
  cardNotes.value.unshift(note)
  newNoteBody.value = ''
}

async function removeCardNote(id: string) {
  await notesService.destroy(id)
  cardNotes.value = cardNotes.value.filter(n => n.id !== id)
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
    await cards.createCard(projectId, { title: newTitle.value.trim(), type: newType.value })
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
          <span
            class="w-2 h-2 rounded-full shrink-0"
            :class="typeConfig(card.type).dot"
          />
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
          <button
            v-if="activeTab === 'attributs' && !editMode"
            class="text-sm px-3 py-1.5 border border-gray-300 dark:border-gray-600
                   text-gray-600 dark:text-gray-300 rounded-lg hover:bg-gray-50
                   dark:hover:bg-gray-800 transition-colors"
            @click="startEdit"
          >Modifier</button>
        </div>

        <!-- Tabs -->
        <div class="flex gap-1 border-b border-gray-200 dark:border-gray-700 mb-5">
          <button
            class="pb-2 px-1 text-sm border-b-2 transition-colors"
            :class="activeTab === 'attributs'
              ? 'border-brand-500 text-brand-600 dark:text-brand-400 font-medium'
              : 'border-transparent text-gray-500 hover:text-gray-700 dark:hover:text-gray-300'"
            @click="activeTab = 'attributs'; editMode = false"
          >Attributs</button>
          <button
            class="pb-2 px-1 ml-4 text-sm border-b-2 transition-colors"
            :class="activeTab === 'liaisons'
              ? 'border-brand-500 text-brand-600 dark:text-brand-400 font-medium'
              : 'border-transparent text-gray-500 hover:text-gray-700 dark:hover:text-gray-300'"
            @click="activeTab = 'liaisons'; editMode = false"
          >Liaisons</button>
          <button
            class="pb-2 px-1 ml-4 text-sm border-b-2 transition-colors"
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
          <div v-else class="space-y-2">
            <div
              v-for="link in cards.activeCard.links"
              :key="link.id"
              class="flex items-center gap-3 p-3 rounded-lg border border-gray-200 dark:border-gray-700
                     bg-white dark:bg-gray-800"
            >
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
                  <template v-if="link.label"> · {{ link.label }}</template>
                </p>
              </div>
            </div>
          </div>
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
