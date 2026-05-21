<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { adminService } from '@/services/admin.service'
import type { GenreAdmin, GenreCategoryAdmin } from '@/types'

// ── State ─────────────────────────────────────────────────────
const categories = ref<GenreCategoryAdmin[]>([])
const proximity = ref<Record<string, Record<string, number>>>({})
const loading = ref(true)
const error = ref('')

// Which category is expanded
const expandedCategory = ref<string | null>(null)

// ── Category form ──────────────────────────────────────────────
const showCategoryForm = ref(false)
const editingCategoryId = ref<string | null>(null)
const savingCategory = ref(false)
const categoryError = ref('')

const categoryForm = ref<Partial<GenreCategoryAdmin>>({
  id: '',
  name: '',
  color: '#888888',
  light_color: '#f8f8f8',
  text_color: '#333333',
  adjacent_categories: [],
})

function resetCategoryForm() {
  editingCategoryId.value = null
  categoryForm.value = { id: '', name: '', color: '#888888', light_color: '#f8f8f8', text_color: '#333333', adjacent_categories: [] }
}

function startCreateCategory() {
  resetCategoryForm()
  showCategoryForm.value = true
}

function startEditCategory(cat: GenreCategoryAdmin) {
  editingCategoryId.value = cat.id
  categoryForm.value = {
    id: cat.id,
    name: cat.name,
    color: cat.color,
    light_color: cat.light_color,
    text_color: cat.text_color,
    adjacent_categories: cat.adjacent_categories ? [...cat.adjacent_categories] : [],
  }
  showCategoryForm.value = true
}

function cancelCategoryForm() {
  showCategoryForm.value = false
  resetCategoryForm()
  categoryError.value = ''
}

async function saveCategory() {
  if (!categoryForm.value.name?.trim()) {
    categoryError.value = 'Le nom est obligatoire.'
    return
  }
  if (!editingCategoryId.value && !categoryForm.value.id?.trim()) {
    categoryError.value = "L'identifiant est obligatoire."
    return
  }
  categoryError.value = ''
  savingCategory.value = true
  try {
    if (editingCategoryId.value) {
      const { category } = await adminService.updateGenreCategory(editingCategoryId.value, categoryForm.value)
      const idx = categories.value.findIndex(c => c.id === editingCategoryId.value)
      if (idx !== -1) {
        categories.value[idx] = { ...categories.value[idx], ...category }
      }
    } else {
      const { category } = await adminService.createGenreCategory(categoryForm.value)
      categories.value.push({ ...category, genres: [] })
    }
    showCategoryForm.value = false
    resetCategoryForm()
  } catch (e: any) {
    categoryError.value = e?.response?.data?.message ?? 'Une erreur est survenue.'
  } finally {
    savingCategory.value = false
  }
}

async function deleteCategory(cat: GenreCategoryAdmin) {
  if (!confirm(`Supprimer l'univers "${cat.name}" et tous ses genres ?`)) return
  await adminService.deleteGenreCategory(cat.id)
  categories.value = categories.value.filter(c => c.id !== cat.id)
}

async function moveCategoryUp(idx: number) {
  if (idx === 0) return
  const arr = [...categories.value]
  const tmp = arr[idx - 1]!
  arr[idx - 1] = arr[idx]!
  arr[idx] = tmp
  categories.value = arr.map((c, i) => ({ ...c, sort_order: i }))
  await adminService.reorderGenreCategories(categories.value.map((c, i) => ({ id: c.id, sort_order: i })))
}

async function moveCategoryDown(idx: number) {
  if (idx === categories.value.length - 1) return
  const arr = [...categories.value]
  const tmp = arr[idx]!
  arr[idx] = arr[idx + 1]!
  arr[idx + 1] = tmp
  categories.value = arr.map((c, i) => ({ ...c, sort_order: i }))
  await adminService.reorderGenreCategories(categories.value.map((c, i) => ({ id: c.id, sort_order: i })))
}

function toggleCategory(id: string) {
  expandedCategory.value = expandedCategory.value === id ? null : id
}

function toggleAdjacent(catId: string) {
  const adj = categoryForm.value.adjacent_categories ?? []
  if (adj.includes(catId)) {
    categoryForm.value.adjacent_categories = adj.filter(a => a !== catId)
  } else {
    categoryForm.value.adjacent_categories = [...adj, catId]
  }
}

// ── Genre form ─────────────────────────────────────────────────
const showGenreForm = ref<string | null>(null) // categoryId
const editingGenreId = ref<string | null>(null)
const savingGenre = ref(false)
const genreError = ref('')

const genreForm = ref<Partial<GenreAdmin>>({
  id: '',
  name: '',
  bridges: null,
})

function resetGenreForm() {
  editingGenreId.value = null
  genreForm.value = { id: '', name: '', bridges: null }
}

function startCreateGenre(categoryId: string) {
  resetGenreForm()
  showGenreForm.value = categoryId
  genreError.value = ''
}

function startEditGenre(categoryId: string, genre: GenreAdmin) {
  editingGenreId.value = genre.id
  genreForm.value = { id: genre.id, name: genre.name, bridges: genre.bridges ? [...genre.bridges] : null }
  showGenreForm.value = categoryId
  genreError.value = ''
}

function cancelGenreForm() {
  showGenreForm.value = null
  resetGenreForm()
  genreError.value = ''
}

async function saveGenre(categoryId: string) {
  if (!genreForm.value.name?.trim()) {
    genreError.value = 'Le nom est obligatoire.'
    return
  }
  if (!editingGenreId.value && !genreForm.value.id?.trim()) {
    genreError.value = "L'identifiant est obligatoire."
    return
  }
  genreError.value = ''
  savingGenre.value = true
  try {
    const cat = categories.value.find(c => c.id === categoryId)
    if (!cat) return

    if (editingGenreId.value) {
      const { genre } = await adminService.updateGenre(categoryId, editingGenreId.value, genreForm.value)
      const idx = cat.genres.findIndex(g => g.id === editingGenreId.value)
      if (idx !== -1) cat.genres[idx] = genre
    } else {
      const payload = { ...genreForm.value, sort_order: cat.genres.length }
      const { genre } = await adminService.createGenre(categoryId, payload)
      cat.genres.push(genre)
    }
    showGenreForm.value = null
    resetGenreForm()
  } catch (e: any) {
    genreError.value = e?.response?.data?.message ?? 'Une erreur est survenue.'
  } finally {
    savingGenre.value = false
  }
}

async function deleteGenre(categoryId: string, genre: GenreAdmin) {
  if (!confirm(`Supprimer le genre "${genre.name}" ?`)) return
  await adminService.deleteGenre(categoryId, genre.id)
  const cat = categories.value.find(c => c.id === categoryId)
  if (cat) cat.genres = cat.genres.filter(g => g.id !== genre.id)
}

async function moveGenreUp(categoryId: string, idx: number) {
  const cat = categories.value.find(c => c.id === categoryId)
  if (!cat || idx === 0) return
  const tmp = cat.genres[idx - 1]!
  cat.genres[idx - 1] = cat.genres[idx]!
  cat.genres[idx] = tmp
  cat.genres = cat.genres.map((g, i) => ({ ...g, sort_order: i }))
  await adminService.reorderGenres(categoryId, cat.genres.map((g, i) => ({ id: g.id, sort_order: i })))
}

async function moveGenreDown(categoryId: string, idx: number) {
  const cat = categories.value.find(c => c.id === categoryId)
  if (!cat || idx === cat.genres.length - 1) return
  const tmp = cat.genres[idx]!
  cat.genres[idx] = cat.genres[idx + 1]!
  cat.genres[idx + 1] = tmp
  cat.genres = cat.genres.map((g, i) => ({ ...g, sort_order: i }))
  await adminService.reorderGenres(categoryId, cat.genres.map((g, i) => ({ id: g.id, sort_order: i })))
}

function toggleBridge(bridgeCatId: string) {
  const bridges = genreForm.value.bridges ?? []
  if (bridges.includes(bridgeCatId)) {
    genreForm.value.bridges = bridges.filter(b => b !== bridgeCatId)
  } else {
    genreForm.value.bridges = [...bridges, bridgeCatId]
  }
}

// ── Proximity matrix ───────────────────────────────────────────
const savingProximity = ref(false)
const proximitySaved = ref(false)

const categoryIds = computed(() => categories.value.map(c => c.id))

function proximityValue(a: string, b: string): number {
  return proximity.value[a]?.[b] ?? 0
}

function setProximity(a: string, b: string, val: number) {
  if (!proximity.value[a]) proximity.value[a] = {}
  if (!proximity.value[b]) proximity.value[b] = {}
  proximity.value[a][b] = val
  proximity.value[b][a] = val
}

function proximityColor(val: number): string {
  if (val >= 0.7) return 'text-green-600 dark:text-green-400'
  if (val >= 0.4) return 'text-amber-600 dark:text-amber-400'
  return 'text-red-600 dark:text-red-400'
}

async function saveProximity() {
  savingProximity.value = true
  proximitySaved.value = false
  try {
    await adminService.updateGenreProximity(proximity.value)
    proximitySaved.value = true
    setTimeout(() => { proximitySaved.value = false }, 2000)
  } catch {
    // silent
  } finally {
    savingProximity.value = false
  }
}

// ── Load ───────────────────────────────────────────────────────
onMounted(async () => {
  try {
    const [catRes, proxRes] = await Promise.all([
      adminService.getGenreCategories(),
      adminService.getGenreProximity(),
    ])
    categories.value = catRes.categories
    proximity.value = proxRes.proximity
  } catch {
    error.value = 'Impossible de charger les données.'
  } finally {
    loading.value = false
  }
})
</script>

<template>
  <div class="p-6 max-w-4xl mx-auto space-y-8">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-100">Genres littéraires</h1>
    </div>

    <!-- Error -->
    <p v-if="error" class="text-sm text-red-600 dark:text-red-400">{{ error }}</p>

    <!-- Loading -->
    <div v-if="loading" class="text-sm text-gray-500 dark:text-gray-400">Chargement…</div>

    <template v-else>
      <!-- ════════════════════════════════════════════
           Section 1 — Univers & Genres
      ════════════════════════════════════════════ -->
      <section class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 overflow-hidden">
        <!-- Section header -->
        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 dark:border-gray-800">
          <h2 class="font-medium text-gray-800 dark:text-gray-200">Univers &amp; Genres</h2>
          <button
            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm rounded-lg bg-brand-600 hover:bg-brand-700 text-white transition-colors"
            @click="startCreateCategory"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Ajouter un univers
          </button>
        </div>

        <!-- Category creation/edit form -->
        <div v-if="showCategoryForm" class="px-5 py-4 bg-gray-50 dark:bg-gray-800/50 border-b border-gray-100 dark:border-gray-800">
          <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
            {{ editingCategoryId ? 'Modifier l\'univers' : 'Nouvel univers' }}
          </h3>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div v-if="!editingCategoryId">
              <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Identifiant (slug)</label>
              <input
                v-model="categoryForm.id"
                type="text"
                placeholder="ex: noire"
                class="w-full text-sm px-3 py-2 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-brand-500"
              />
            </div>
            <div>
              <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Nom</label>
              <input
                v-model="categoryForm.name"
                type="text"
                placeholder="Littérature noire"
                class="w-full text-sm px-3 py-2 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-brand-500"
              />
            </div>
            <div class="flex gap-3">
              <div class="flex-1">
                <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Couleur principale</label>
                <div class="flex items-center gap-2">
                  <input v-model="categoryForm.color" type="color" class="w-10 h-9 rounded border border-gray-200 dark:border-gray-700 cursor-pointer p-0.5 bg-white dark:bg-gray-900" />
                  <input v-model="categoryForm.color" type="text" class="flex-1 text-sm px-2 py-2 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-brand-500" />
                </div>
              </div>
              <div class="flex-1">
                <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Couleur claire</label>
                <div class="flex items-center gap-2">
                  <input v-model="categoryForm.light_color" type="color" class="w-10 h-9 rounded border border-gray-200 dark:border-gray-700 cursor-pointer p-0.5 bg-white dark:bg-gray-900" />
                  <input v-model="categoryForm.light_color" type="text" class="flex-1 text-sm px-2 py-2 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-brand-500" />
                </div>
              </div>
              <div class="flex-1">
                <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Couleur texte</label>
                <div class="flex items-center gap-2">
                  <input v-model="categoryForm.text_color" type="color" class="w-10 h-9 rounded border border-gray-200 dark:border-gray-700 cursor-pointer p-0.5 bg-white dark:bg-gray-900" />
                  <input v-model="categoryForm.text_color" type="text" class="flex-1 text-sm px-2 py-2 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-brand-500" />
                </div>
              </div>
            </div>
            <div>
              <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Univers adjacents</label>
              <div class="flex flex-wrap gap-2">
                <label
                  v-for="cat in categories.filter(c => c.id !== categoryForm.id)"
                  :key="cat.id"
                  class="flex items-center gap-1.5 text-sm cursor-pointer"
                >
                  <input
                    type="checkbox"
                    :checked="categoryForm.adjacent_categories?.includes(cat.id)"
                    @change="toggleAdjacent(cat.id)"
                    class="rounded border-gray-300 text-brand-600 focus:ring-brand-500"
                  />
                  <span
                    class="px-2 py-0.5 rounded-full text-xs font-medium text-white"
                    :style="{ backgroundColor: cat.color }"
                  >{{ cat.name }}</span>
                </label>
              </div>
            </div>
          </div>
          <p v-if="categoryError" class="mt-2 text-xs text-red-600 dark:text-red-400">{{ categoryError }}</p>
          <div class="flex gap-2 mt-3">
            <button
              class="px-4 py-1.5 text-sm rounded-lg bg-brand-600 hover:bg-brand-700 text-white disabled:opacity-50 transition-colors"
              :disabled="savingCategory"
              @click="saveCategory"
            >
              {{ savingCategory ? 'Enregistrement…' : 'Enregistrer' }}
            </button>
            <button
              class="px-4 py-1.5 text-sm rounded-lg border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors"
              @click="cancelCategoryForm"
            >
              Annuler
            </button>
          </div>
        </div>

        <!-- Category list -->
        <div v-if="categories.length === 0" class="px-5 py-8 text-center text-sm text-gray-400 dark:text-gray-600">
          Aucun univers. Commence par en créer un.
        </div>

        <div
          v-for="(cat, catIdx) in categories"
          :key="cat.id"
          class="border-b border-gray-100 dark:border-gray-800 last:border-b-0"
        >
          <!-- Category row -->
          <div class="flex items-center gap-3 px-5 py-3">
            <!-- Color dot -->
            <span class="w-3 h-3 rounded-full shrink-0" :style="{ backgroundColor: cat.color }" />

            <!-- Category name -->
            <button
              class="flex-1 text-left text-sm font-medium text-gray-800 dark:text-gray-200 hover:text-brand-600 dark:hover:text-brand-400 transition-colors"
              @click="toggleCategory(cat.id)"
            >
              {{ cat.name }}
              <span class="ml-2 text-xs font-normal text-gray-400 dark:text-gray-500">
                {{ cat.genres.length }} genre{{ cat.genres.length !== 1 ? 's' : '' }}
              </span>
            </button>

            <!-- Actions -->
            <div class="flex items-center gap-1 shrink-0">
              <button
                class="p-1 rounded text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
                title="Monter"
                :disabled="catIdx === 0"
                @click="moveCategoryUp(catIdx)"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/>
                </svg>
              </button>
              <button
                class="p-1 rounded text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
                title="Descendre"
                :disabled="catIdx === categories.length - 1"
                @click="moveCategoryDown(catIdx)"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
              </button>
              <button
                class="p-1 rounded text-gray-400 hover:text-brand-600 dark:hover:text-brand-400 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
                title="Modifier"
                @click="startEditCategory(cat)"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
              </button>
              <button
                class="p-1 rounded text-gray-400 hover:text-red-600 dark:hover:text-red-400 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
                title="Supprimer"
                @click="deleteCategory(cat)"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
              </button>
              <button
                class="p-1 rounded text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
                @click="toggleCategory(cat.id)"
              >
                <svg
                  class="w-4 h-4 transition-transform"
                  :class="expandedCategory === cat.id ? 'rotate-180' : ''"
                  fill="none" stroke="currentColor" viewBox="0 0 24 24"
                >
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
              </button>
            </div>
          </div>

          <!-- Expanded genre list -->
          <div v-if="expandedCategory === cat.id" class="px-5 pb-4 pt-1 bg-gray-50 dark:bg-gray-800/30">
            <!-- Genre list -->
            <div class="space-y-1 mb-3">
              <div
                v-for="(genre, gIdx) in cat.genres"
                :key="genre.id"
                class="flex items-center gap-2 py-1.5 px-2 rounded-lg hover:bg-white dark:hover:bg-gray-800 transition-colors group"
              >
                <!-- Name badge -->
                <span
                  class="text-xs font-medium px-2 py-0.5 rounded-full text-white"
                  :style="{ backgroundColor: cat.color }"
                >
                  {{ genre.name }}
                </span>
                <!-- Bridge badges -->
                <span
                  v-for="bridgeId in (genre.bridges ?? [])"
                  :key="bridgeId"
                  class="text-xs px-1.5 py-0.5 rounded-full border"
                  :style="{
                    borderColor: categories.find(c => c.id === bridgeId)?.color ?? '#888',
                    color: categories.find(c => c.id === bridgeId)?.color ?? '#888'
                  }"
                >
                  {{ categories.find(c => c.id === bridgeId)?.name ?? bridgeId }}
                </span>
                <span class="text-xs text-gray-400 dark:text-gray-600 ml-1 font-mono">{{ genre.id }}</span>

                <!-- Actions -->
                <div class="ml-auto flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                  <button
                    class="p-0.5 rounded text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 disabled:opacity-30 transition-colors"
                    :disabled="gIdx === 0"
                    @click="moveGenreUp(cat.id, gIdx)"
                  >
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/>
                    </svg>
                  </button>
                  <button
                    class="p-0.5 rounded text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 disabled:opacity-30 transition-colors"
                    :disabled="gIdx === cat.genres.length - 1"
                    @click="moveGenreDown(cat.id, gIdx)"
                  >
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                  </button>
                  <button
                    class="p-0.5 rounded text-gray-400 hover:text-brand-600 dark:hover:text-brand-400 transition-colors"
                    @click="startEditGenre(cat.id, genre)"
                  >
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                  </button>
                  <button
                    class="p-0.5 rounded text-gray-400 hover:text-red-600 dark:hover:text-red-400 transition-colors"
                    @click="deleteGenre(cat.id, genre)"
                  >
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                  </button>
                </div>
              </div>

              <p v-if="cat.genres.length === 0" class="text-xs text-gray-400 dark:text-gray-600 py-2 italic">
                Aucun genre dans cet univers.
              </p>
            </div>

            <!-- Genre inline form -->
            <div v-if="showGenreForm === cat.id" class="mt-3 p-3 rounded-lg bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700">
              <h4 class="text-xs font-medium text-gray-600 dark:text-gray-400 mb-2">
                {{ editingGenreId ? 'Modifier le genre' : 'Nouveau genre' }}
              </h4>
              <div class="flex flex-wrap gap-2 mb-2">
                <div v-if="!editingGenreId" class="flex-1 min-w-[140px]">
                  <label class="block text-xs text-gray-400 mb-0.5">Identifiant (slug)</label>
                  <input
                    v-model="genreForm.id"
                    type="text"
                    placeholder="ex: polar"
                    class="w-full text-sm px-2 py-1.5 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-brand-500"
                  />
                </div>
                <div class="flex-1 min-w-[160px]">
                  <label class="block text-xs text-gray-400 mb-0.5">Nom</label>
                  <input
                    v-model="genreForm.name"
                    type="text"
                    placeholder="ex: Polar"
                    class="w-full text-sm px-2 py-1.5 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-brand-500"
                  />
                </div>
              </div>
              <!-- Bridges -->
              <div v-if="categories.filter(c => c.id !== cat.id).length > 0" class="mb-2">
                <label class="block text-xs text-gray-400 mb-1">Ponts vers d'autres univers</label>
                <div class="flex flex-wrap gap-2">
                  <label
                    v-for="otherCat in categories.filter(c => c.id !== cat.id)"
                    :key="otherCat.id"
                    class="flex items-center gap-1.5 text-xs cursor-pointer"
                  >
                    <input
                      type="checkbox"
                      :checked="(genreForm.bridges ?? []).includes(otherCat.id)"
                      @change="toggleBridge(otherCat.id)"
                      class="rounded border-gray-300 text-brand-600 focus:ring-brand-500"
                    />
                    <span
                      class="px-1.5 py-0.5 rounded-full text-white"
                      :style="{ backgroundColor: otherCat.color }"
                    >{{ otherCat.name }}</span>
                  </label>
                </div>
              </div>
              <p v-if="genreError" class="text-xs text-red-600 dark:text-red-400 mb-2">{{ genreError }}</p>
              <div class="flex gap-2">
                <button
                  class="px-3 py-1 text-xs rounded-lg bg-brand-600 hover:bg-brand-700 text-white disabled:opacity-50 transition-colors"
                  :disabled="savingGenre"
                  @click="saveGenre(cat.id)"
                >
                  {{ savingGenre ? 'Enregistrement…' : 'Enregistrer' }}
                </button>
                <button
                  class="px-3 py-1 text-xs rounded-lg border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors"
                  @click="cancelGenreForm"
                >
                  Annuler
                </button>
              </div>
            </div>

            <!-- Add genre button -->
            <button
              v-if="showGenreForm !== cat.id"
              class="mt-2 inline-flex items-center gap-1 text-xs text-brand-600 dark:text-brand-400 hover:text-brand-700 dark:hover:text-brand-300 transition-colors"
              @click="startCreateGenre(cat.id)"
            >
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
              </svg>
              Ajouter un genre
            </button>
          </div>
        </div>
      </section>

      <!-- ════════════════════════════════════════════
           Section 2 — Matrice de proximité
      ════════════════════════════════════════════ -->
      <section class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 overflow-hidden">
        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 dark:border-gray-800">
          <h2 class="font-medium text-gray-800 dark:text-gray-200">Matrice de proximité des univers</h2>
          <button
            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm rounded-lg transition-colors"
            :class="proximitySaved
              ? 'bg-green-500 text-white'
              : 'bg-brand-600 hover:bg-brand-700 text-white'"
            :disabled="savingProximity"
            @click="saveProximity"
          >
            <svg v-if="proximitySaved" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            {{ proximitySaved ? 'Sauvegardé !' : savingProximity ? 'Enregistrement…' : 'Sauvegarder la matrice' }}
          </button>
        </div>

        <div class="p-5">
          <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">
            La matrice est symétrique : modifier une valeur met à jour automatiquement la valeur inverse.
          </p>

          <div v-if="categoryIds.length < 2" class="text-sm text-gray-400 dark:text-gray-600 italic">
            Il faut au moins 2 univers pour configurer la matrice.
          </div>

          <div v-else class="space-y-4">
            <div
              v-for="(rowId, rIdx) in categoryIds"
              :key="rowId"
            >
              <div class="space-y-2">
                <div
                  v-for="colId in categoryIds.slice(rIdx + 1)"
                  :key="colId"
                  class="flex items-center gap-4"
                >
                  <!-- Labels -->
                  <div class="w-56 shrink-0 flex items-center gap-2">
                    <span
                      class="inline-block text-xs font-medium px-2 py-0.5 rounded-full text-white"
                      :style="{ backgroundColor: categories.find(c => c.id === rowId)?.color ?? '#888' }"
                    >
                      {{ categories.find(c => c.id === rowId)?.name ?? rowId }}
                    </span>
                    <span class="text-gray-400 dark:text-gray-600 text-xs">↔</span>
                    <span
                      class="inline-block text-xs font-medium px-2 py-0.5 rounded-full text-white"
                      :style="{ backgroundColor: categories.find(c => c.id === colId)?.color ?? '#888' }"
                    >
                      {{ categories.find(c => c.id === colId)?.name ?? colId }}
                    </span>
                  </div>

                  <!-- Slider -->
                  <input
                    type="range"
                    min="0"
                    max="1"
                    step="0.05"
                    :value="proximityValue(rowId, colId)"
                    class="flex-1 accent-brand-600"
                    @input="setProximity(rowId, colId, parseFloat(($event.target as HTMLInputElement).value))"
                  />

                  <!-- Value -->
                  <span
                    class="w-10 text-right text-sm font-mono font-medium shrink-0"
                    :class="proximityColor(proximityValue(rowId, colId))"
                  >
                    {{ proximityValue(rowId, colId).toFixed(2) }}
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>
    </template>
  </div>
</template>
