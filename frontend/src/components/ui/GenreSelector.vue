<script setup lang="ts">
import { ref, computed } from 'vue'
import {
  GENRE_CATEGORIES,
  getGenreName,
  getCategoryForGenre,
  computeFusion,
  type CategoryId,
} from '@/data/genres'

const props = defineProps<{
  modelValue: string[]
}>()

const emit = defineEmits<{
  'update:modelValue': [value: string[]]
}>()

const open = ref(false)
const expandedCat = ref<CategoryId | null>(null)

const fusion = computed(() => computeFusion(props.modelValue))

function toggle(id: string) {
  const next = props.modelValue.includes(id)
    ? props.modelValue.filter((g) => g !== id)
    : [...props.modelValue, id]
  emit('update:modelValue', next)
}

function selectedInCategory(catId: CategoryId): number {
  const cat = GENRE_CATEGORIES.find((c) => c.id === catId)
  return cat ? cat.genres.filter((g) => props.modelValue.includes(g.id)).length : 0
}

function toggleCategory(catId: CategoryId) {
  expandedCat.value = expandedCat.value === catId ? null : catId
}

function close() {
  open.value = false
  expandedCat.value = null
}
</script>

<template>
  <!-- Trigger -->
  <button
    type="button"
    class="w-full min-h-[42px] text-left rounded-lg border px-3 py-2
           border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800
           focus:outline-none focus:ring-2 focus:ring-brand-500 transition-colors
           hover:border-gray-400 dark:hover:border-gray-600"
    @click="open = true"
  >
    <div v-if="modelValue.length === 0" class="text-sm text-gray-400">
      Choisir des genres…
    </div>
    <div v-else class="flex flex-wrap gap-1">
      <span
        v-for="id in modelValue"
        :key="id"
        class="inline-flex items-center text-xs px-2 py-0.5 rounded-full font-medium"
        :style="{
          background: getCategoryForGenre(id)?.lightColor,
          color: getCategoryForGenre(id)?.textColor,
          border: `1px solid ${getCategoryForGenre(id)?.color}40`,
        }"
      >
        {{ getGenreName(id) }}
      </span>
    </div>
  </button>

  <!-- Bottom sheet -->
  <Teleport to="body">
    <Transition name="gs-backdrop">
      <div v-if="open" class="fixed inset-0 z-[200] flex flex-col justify-end">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="close" />

        <div class="relative bg-white dark:bg-gray-900 rounded-t-3xl shadow-2xl flex flex-col"
             style="max-height: 92dvh">

          <!-- Handle -->
          <div class="flex justify-center pt-3 pb-1 shrink-0">
            <div class="w-10 h-1 rounded-full bg-gray-200 dark:bg-gray-700" />
          </div>

          <!-- Header -->
          <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100 dark:border-gray-800 shrink-0">
            <div>
              <h3 class="font-semibold text-gray-900 dark:text-gray-100">Genres littéraires</h3>
              <p class="text-xs text-gray-400 mt-0.5">
                {{ modelValue.length === 0
                  ? 'Aucun genre sélectionné'
                  : `${modelValue.length} genre${modelValue.length > 1 ? 's' : ''} choisi${modelValue.length > 1 ? 's' : ''}` }}
              </p>
            </div>
            <button
              class="px-4 py-1.5 rounded-lg bg-brand-600 hover:bg-brand-700 text-white text-sm font-medium transition-colors"
              @click="close"
            >
              Valider
            </button>
          </div>

          <!-- Selected genres + fusion meter -->
          <Transition name="gs-expand">
            <div
              v-if="modelValue.length > 0"
              class="px-4 pt-3 pb-3 border-b border-gray-100 dark:border-gray-800 shrink-0"
            >
              <div class="flex flex-wrap gap-1.5 mb-2">
                <button
                  v-for="id in modelValue"
                  :key="id"
                  type="button"
                  class="inline-flex items-center gap-1 text-xs px-2.5 py-1 rounded-full font-medium
                         transition-all duration-150 active:scale-95"
                  :style="{
                    background: getCategoryForGenre(id)?.lightColor,
                    color: getCategoryForGenre(id)?.textColor,
                    border: `1px solid ${getCategoryForGenre(id)?.color}50`,
                  }"
                  @click="toggle(id)"
                >
                  {{ getGenreName(id) }}
                  <svg class="w-3 h-3 opacity-60" viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                    <path d="M9 3L3 9M3 3l6 6" />
                  </svg>
                </button>
              </div>

              <!-- Fusion meter -->
              <div v-if="modelValue.length > 1" class="mt-2 space-y-1">
                <div class="flex items-center gap-2">
                  <div class="flex-1 h-1.5 bg-gray-100 dark:bg-gray-800 rounded-full overflow-hidden">
                    <div
                      class="h-full rounded-full transition-all duration-500"
                      :style="{ width: `${fusion.score * 100}%`, background: fusion.color }"
                    />
                  </div>
                  <span class="text-xs font-semibold shrink-0" :style="{ color: fusion.color }">
                    {{ fusion.label }}
                  </span>
                </div>
                <p class="text-[10px] text-gray-400 leading-tight">{{ fusion.description }}</p>
              </div>
            </div>
          </Transition>

          <!-- Category list -->
          <div class="flex-1 overflow-y-auto p-3 space-y-2">
            <div
              v-for="cat in GENRE_CATEGORIES"
              :key="cat.id"
              class="rounded-xl overflow-hidden border transition-shadow"
              :class="expandedCat === cat.id
                ? 'border-gray-200 dark:border-gray-700 shadow-sm'
                : 'border-gray-100 dark:border-gray-800'"
            >
              <!-- Category header -->
              <button
                type="button"
                class="w-full flex items-center gap-3 px-4 py-3 text-left transition-colors"
                :style="expandedCat === cat.id ? { background: cat.lightColor } : {}"
                :class="expandedCat !== cat.id
                  ? 'bg-white dark:bg-gray-900 hover:bg-gray-50 dark:hover:bg-gray-800'
                  : ''"
                @click="toggleCategory(cat.id)"
              >
                <div
                  class="w-2.5 h-2.5 rounded-full shrink-0"
                  :style="{ background: cat.color }"
                />
                <span
                  class="flex-1 text-sm font-medium transition-colors"
                  :style="expandedCat === cat.id ? { color: cat.textColor } : {}"
                  :class="expandedCat !== cat.id ? 'text-gray-800 dark:text-gray-200' : ''"
                >
                  {{ cat.name }}
                </span>

                <span
                  v-if="selectedInCategory(cat.id) > 0"
                  class="text-xs font-bold px-1.5 py-0.5 rounded-full mr-1"
                  :style="{ background: cat.color + '28', color: cat.textColor }"
                >
                  {{ selectedInCategory(cat.id) }}
                </span>

                <span class="text-xs text-gray-400 mr-1">{{ cat.genres.length }}</span>

                <svg
                  class="w-4 h-4 text-gray-400 transition-transform duration-200 shrink-0"
                  :class="expandedCat === cat.id ? 'rotate-180' : ''"
                  viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                >
                  <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                </svg>
              </button>

              <!-- Genre bubbles -->
              <Transition
                enter-active-class="transition-all duration-300 ease-out overflow-hidden"
                enter-from-class="opacity-0 max-h-0"
                enter-to-class="opacity-100 max-h-[500px]"
                leave-active-class="transition-all duration-200 ease-in overflow-hidden"
                leave-from-class="opacity-100 max-h-[500px]"
                leave-to-class="opacity-0 max-h-0"
              >
                <div
                  v-if="expandedCat === cat.id"
                  class="flex flex-wrap gap-2 px-4 pb-4 pt-2"
                  :style="{ background: cat.lightColor }"
                >
                  <button
                    v-for="genre in cat.genres"
                    :key="genre.id"
                    type="button"
                    class="text-xs px-3 py-1.5 rounded-full border font-medium transition-all duration-150 active:scale-95"
                    :style="modelValue.includes(genre.id)
                      ? { background: cat.color, color: '#fff', borderColor: cat.color, boxShadow: `0 0 0 3px ${cat.color}30` }
                      : { background: 'white', color: cat.textColor, borderColor: cat.color + '60' }"
                    @click.stop="toggle(genre.id)"
                  >
                    {{ genre.name }}<template v-if="genre.bridges">
                      <span class="ml-1 opacity-50 text-[10px]">↔</span>
                    </template>
                  </button>
                </div>
              </Transition>
            </div>

            <p class="text-center text-[10px] text-gray-300 dark:text-gray-700 py-2">
              ↔ = genre pont entre deux univers
            </p>
          </div>

          <!-- Safe area -->
          <div class="shrink-0 pb-safe" style="padding-bottom: env(safe-area-inset-bottom, 12px)" />
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
.gs-backdrop-enter-active,
.gs-backdrop-leave-active {
  transition: opacity 0.25s ease;
}
.gs-backdrop-enter-from,
.gs-backdrop-leave-to {
  opacity: 0;
}

.gs-backdrop-enter-active > div:last-child {
  transition: transform 0.35s cubic-bezier(0.32, 0.72, 0, 1);
}
.gs-backdrop-leave-active > div:last-child {
  transition: transform 0.25s ease-in;
}
.gs-backdrop-enter-from > div:last-child,
.gs-backdrop-leave-to > div:last-child {
  transform: translateY(100%);
}

.gs-expand-enter-active,
.gs-expand-leave-active {
  transition: all 0.2s ease;
  overflow: hidden;
}
.gs-expand-enter-from,
.gs-expand-leave-to {
  opacity: 0;
  max-height: 0 !important;
}
.gs-expand-enter-to,
.gs-expand-leave-from {
  opacity: 1;
  max-height: 300px;
}
</style>
