<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { adminService } from '@/services/admin.service'
import { useFontsStore } from '@/stores/fonts.store'
import type { AvailableFont } from '@/types'

const fontsStore = useFontsStore()

const fonts   = ref<AvailableFont[]>([])
const loading = ref(true)
const saving  = ref(false)
const error   = ref('')

// ── Chargement ────────────────────────────────────────────────
onMounted(async () => {
  try {
    const { fonts: list } = await adminService.getFonts()
    fonts.value = [...list].sort((a, b) => a.sort_order - b.sort_order || a.name.localeCompare(b.name))
  } finally {
    loading.value = false
  }
})

const enabledFonts   = computed(() => fonts.value.filter(f => f.enabled))
const disabledFonts  = computed(() => fonts.value.filter(f => !f.enabled))

const CATEGORIES: AvailableFont['category'][] = ['serif', 'sans-serif', 'monospace']
const categoryLabel: Record<string, string> = {
  'serif': 'Serif', 'sans-serif': 'Sans-serif', 'monospace': 'Monospace',
}

// ── Toggle activé/désactivé ───────────────────────────────────
async function toggle(font: AvailableFont) {
  font.enabled = !font.enabled
  await adminService.updateFont(font.id, { enabled: font.enabled })
  // Recharger le store public
  fontsStore.loaded = false
  await fontsStore.loadFonts()
}

// ── Réordonner (flèches) ──────────────────────────────────────
async function moveUp(font: AvailableFont) {
  const idx = enabledFonts.value.findIndex(f => f.id === font.id)
  if (idx <= 0) return
  const prev = enabledFonts.value[idx - 1]!
  ;[prev.sort_order, font.sort_order] = [font.sort_order, prev.sort_order]
  fonts.value = [...fonts.value].sort((a, b) => a.sort_order - b.sort_order || a.name.localeCompare(b.name))
  await adminService.reorderFonts(enabledFonts.value.map(f => f.id))
}

async function moveDown(font: AvailableFont) {
  const idx = enabledFonts.value.findIndex(f => f.id === font.id)
  if (idx >= enabledFonts.value.length - 1) return
  const next = enabledFonts.value[idx + 1]!
  ;[next.sort_order, font.sort_order] = [font.sort_order, next.sort_order]
  fonts.value = [...fonts.value].sort((a, b) => a.sort_order - b.sort_order || a.name.localeCompare(b.name))
  await adminService.reorderFonts(enabledFonts.value.map(f => f.id))
}

// ── Supprimer ─────────────────────────────────────────────────
async function deleteFont(font: AvailableFont) {
  if (!confirm(`Supprimer « ${font.name} » ?`)) return
  await adminService.deleteFont(font.id)
  fonts.value = fonts.value.filter(f => f.id !== font.id)
  fontsStore.loaded = false
  await fontsStore.loadFonts()
}

// ── Formulaire ajout ──────────────────────────────────────────
const showForm   = ref(false)
const formName   = ref('')
const formSlug   = ref('')
const formCssFamily = ref('')
const formCategory  = ref<AvailableFont['category']>('serif')
const formError  = ref('')
const formLoading = ref(false)

// Auto-génère le slug Google Fonts à partir du nom
function syncSlug() {
  formSlug.value = formName.value.trim().replace(/\s+/g, '+')
  formCssFamily.value = formName.value.trim()
    ? `"${formName.value.trim()}", ${formCategory.value}`
    : ''
}

function syncFamily() {
  formCssFamily.value = formName.value.trim()
    ? `"${formName.value.trim()}", ${formCategory.value}`
    : ''
}

async function submitFont() {
  formError.value = ''
  if (!formName.value.trim() || !formSlug.value.trim()) {
    formError.value = 'Le nom et le slug sont requis.'
    return
  }
  formLoading.value = true
  try {
    const { font } = await adminService.createFont({
      name: formName.value.trim(),
      google_font_slug: formSlug.value.trim(),
      css_family: formCssFamily.value.trim() || `"${formName.value.trim()}", ${formCategory.value}`,
      category: formCategory.value,
    })
    fonts.value.push(font)
    fonts.value.sort((a, b) => a.sort_order - b.sort_order || a.name.localeCompare(b.name))
    showForm.value = false
    formName.value = ''
    formSlug.value = ''
    formCssFamily.value = ''
    fontsStore.loaded = false
    await fontsStore.loadFonts()
  } catch (e: any) {
    formError.value = e?.response?.data?.message ?? 'Erreur lors de l\'ajout.'
  } finally {
    formLoading.value = false
  }
}

// ── Aperçu Google Fonts ───────────────────────────────────────
const previewText = 'La nuit était noire et silencieuse.'
</script>

<template>
  <div class="p-6 max-w-3xl">
    <div class="flex items-center justify-between mb-6">
      <div>
        <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-100">Polices disponibles</h1>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
          Gérez les polices proposées aux utilisateurs dans l'éditeur.
          L'ordre ici est l'ordre d'affichage dans les préférences.
        </p>
      </div>
      <button
        class="flex items-center gap-1.5 px-3 py-2 text-sm bg-brand-600 hover:bg-brand-700
               text-white rounded-lg transition-colors"
        @click="showForm = !showForm"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        Ajouter
      </button>
    </div>

    <!-- Formulaire ajout -->
    <Transition name="slide">
      <div
        v-if="showForm"
        class="mb-6 p-4 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl"
      >
        <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-4">
          Nouvelle police Google Fonts
        </h2>
        <div class="grid grid-cols-2 gap-3 mb-3">
          <div>
            <label class="block text-xs text-gray-500 mb-1">Nom d'affichage</label>
            <input
              v-model="formName"
              type="text"
              placeholder="ex : Crimson Text"
              class="w-full text-sm rounded-lg border border-gray-300 dark:border-gray-600
                     bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200
                     px-3 py-2 focus:outline-none focus:ring-1 focus:ring-brand-500"
              @input="syncSlug"
            />
          </div>
          <div>
            <label class="block text-xs text-gray-500 mb-1">Catégorie</label>
            <select
              v-model="formCategory"
              class="w-full text-sm rounded-lg border border-gray-300 dark:border-gray-600
                     bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200
                     px-3 py-2 focus:outline-none focus:ring-1 focus:ring-brand-500"
              @change="syncFamily"
            >
              <option v-for="c in CATEGORIES" :key="c" :value="c">{{ categoryLabel[c] }}</option>
            </select>
          </div>
          <div>
            <label class="block text-xs text-gray-500 mb-1">
              Slug Google Fonts
              <span class="text-gray-400 font-normal">(nom exact, espaces → +)</span>
            </label>
            <input
              v-model="formSlug"
              type="text"
              placeholder="ex : Crimson+Text  ou  Noto+Serif"
              class="w-full text-sm rounded-lg border border-gray-300 dark:border-gray-600
                     bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200
                     px-3 py-2 focus:outline-none focus:ring-1 focus:ring-brand-500"
            />
            <a
              v-if="formSlug"
              :href="`https://fonts.google.com/specimen/${formSlug.replace(/\+/g, ' ')}`"
              target="_blank"
              rel="noopener"
              class="text-xs text-brand-500 hover:underline mt-0.5 block"
            >Voir sur Google Fonts ↗</a>
          </div>
          <div>
            <label class="block text-xs text-gray-500 mb-1">
              CSS font-family
              <span class="text-gray-400 font-normal">(auto-rempli)</span>
            </label>
            <input
              v-model="formCssFamily"
              type="text"
              placeholder="ex : &quot;Crimson Text&quot;, serif"
              class="w-full text-sm rounded-lg border border-gray-300 dark:border-gray-600
                     bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200
                     px-3 py-2 focus:outline-none focus:ring-1 focus:ring-brand-500"
            />
          </div>
        </div>

        <p v-if="formError" class="text-xs text-red-500 mb-3">{{ formError }}</p>

        <div class="flex gap-2">
          <button
            class="px-4 py-2 text-sm bg-brand-600 hover:bg-brand-700 text-white rounded-lg
                   disabled:opacity-50 transition-colors"
            :disabled="formLoading"
            @click="submitFont"
          >{{ formLoading ? 'Ajout…' : 'Ajouter la police' }}</button>
          <button
            class="px-4 py-2 text-sm text-gray-600 dark:text-gray-400 hover:text-gray-800
                   dark:hover:text-gray-200 transition-colors"
            @click="showForm = false"
          >Annuler</button>
        </div>
      </div>
    </Transition>

    <div v-if="loading" class="text-sm text-gray-400">Chargement…</div>

    <template v-else>
      <!-- Polices actives -->
      <section class="mb-8">
        <h2 class="text-xs font-semibold uppercase tracking-widest text-gray-400 mb-3">
          Polices actives ({{ enabledFonts.length }})
        </h2>

        <div class="space-y-2">
          <div
            v-for="(font, idx) in enabledFonts"
            :key="font.id"
            class="flex items-center gap-3 p-3 bg-white dark:bg-gray-900
                   border border-gray-200 dark:border-gray-700 rounded-xl"
          >
            <!-- Ordre -->
            <div class="flex flex-col gap-0.5">
              <button
                :disabled="idx === 0"
                class="text-gray-400 hover:text-gray-600 disabled:opacity-20 transition-colors"
                title="Monter"
                @click="moveUp(font)"
              >
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/>
                </svg>
              </button>
              <button
                :disabled="idx === enabledFonts.length - 1"
                class="text-gray-400 hover:text-gray-600 disabled:opacity-20 transition-colors"
                title="Descendre"
                @click="moveDown(font)"
              >
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
              </button>
            </div>

            <!-- Aperçu -->
            <div class="flex-1 min-w-0">
              <div class="flex items-center gap-2 mb-0.5">
                <span class="text-sm font-medium text-gray-800 dark:text-gray-100">{{ font.name }}</span>
                <span
                  class="text-[10px] px-1.5 py-0.5 rounded-full border"
                  :class="{
                    'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-900/20 dark:text-amber-300 dark:border-amber-700': font.category === 'serif',
                    'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-900/20 dark:text-blue-300 dark:border-blue-700': font.category === 'sans-serif',
                    'bg-gray-100 text-gray-600 border-gray-200 dark:bg-gray-800 dark:text-gray-400': font.category === 'monospace',
                  }"
                >{{ categoryLabel[font.category] }}</span>
              </div>
              <p
                class="text-base text-gray-500 dark:text-gray-400 truncate"
                :style="{ fontFamily: font.css_family }"
              >{{ previewText }}</p>
            </div>

            <!-- Actions -->
            <div class="flex items-center gap-2 shrink-0">
              <button
                class="text-xs px-2 py-1 rounded-lg border border-gray-300 dark:border-gray-600
                       text-gray-500 hover:border-red-300 hover:text-red-500 transition-colors"
                @click="toggle(font)"
              >Désactiver</button>
              <button
                class="text-gray-400 hover:text-red-500 transition-colors"
                title="Supprimer"
                @click="deleteFont(font)"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
              </button>
            </div>
          </div>

          <p v-if="!enabledFonts.length" class="text-sm text-gray-400 py-2">
            Aucune police active.
          </p>
        </div>
      </section>

      <!-- Polices désactivées -->
      <section v-if="disabledFonts.length">
        <h2 class="text-xs font-semibold uppercase tracking-widest text-gray-400 mb-3">
          Désactivées ({{ disabledFonts.length }})
        </h2>

        <div class="space-y-2">
          <div
            v-for="font in disabledFonts"
            :key="font.id"
            class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-900/50
                   border border-dashed border-gray-200 dark:border-gray-700 rounded-xl opacity-60"
          >
            <div class="flex-1 min-w-0">
              <div class="flex items-center gap-2 mb-0.5">
                <span class="text-sm text-gray-600 dark:text-gray-400">{{ font.name }}</span>
                <span class="text-[10px] px-1.5 py-0.5 rounded-full bg-gray-100 dark:bg-gray-800 text-gray-500">
                  {{ categoryLabel[font.category] }}
                </span>
              </div>
              <p
                class="text-base text-gray-400 truncate"
                :style="{ fontFamily: font.css_family }"
              >{{ previewText }}</p>
            </div>
            <div class="flex items-center gap-2">
              <button
                class="text-xs px-2 py-1 rounded-lg border border-brand-300 dark:border-brand-700
                       text-brand-600 dark:text-brand-400 hover:bg-brand-50 dark:hover:bg-brand-900/20
                       transition-colors"
                @click="toggle(font)"
              >Activer</button>
              <button
                class="text-gray-400 hover:text-red-500 transition-colors"
                @click="deleteFont(font)"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
              </button>
            </div>
          </div>
        </div>
      </section>
    </template>
  </div>
</template>

<style scoped>
.slide-enter-active, .slide-leave-active {
  transition: all 0.2s ease;
}
.slide-enter-from, .slide-leave-to {
  opacity: 0;
  transform: translateY(-8px);
}
</style>
