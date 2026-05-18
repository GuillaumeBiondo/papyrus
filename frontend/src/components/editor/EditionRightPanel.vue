<script setup lang="ts">
import { onMounted, ref, watch } from 'vue'
import { useEditionStore } from '@/stores/edition.store'
import { useAppConfigStore } from '@/stores/appConfig.store'
import { useAuthStore } from '@/stores/auth.store'
import { editionPresetsService } from '@/services/editionPresets.service'
import type { EditionSettings, EditionPreset } from '@/types'

const props = defineProps<{ projectId: string }>()

type Tab = 'layout' | 'typography' | 'structure' | 'headers'
const activeTab = ref<Tab>('layout')

const edition    = useEditionStore()
const appConfig  = useAppConfigStore()
const auth       = useAuthStore()
const settings   = ref<EditionSettings | null>(null)
const loading    = ref(true)
let saveTimer: ReturnType<typeof setTimeout> | null = null

// ── Préréglages ───────────────────────────────────────────────
const presets         = ref<EditionPreset[]>([])
const selectedPreset  = ref<EditionPreset | null>(null)
const showSaveInput   = ref(false)
const saveInputName   = ref('')
const presetsLocked   = ref(false)

async function loadPresets() {
  presetsLocked.value = !!(appConfig.config?.edition_presets_is_premium && !auth.user?.effective_premium)
  if (presetsLocked.value) return
  try {
    const { data } = await editionPresetsService.list()
    presets.value = data
  } catch { /* silencieux si 403 */ }
}

function applyPreset(preset: EditionPreset) {
  if (!preset?.settings) return
  skipNextSave = true
  settings.value = JSON.parse(JSON.stringify(preset.settings))
  selectedPreset.value = preset
  if (settings.value) edition.patchSettings(settings.value)
}

async function saveNewPreset() {
  const name = saveInputName.value.trim()
  if (!name || !settings.value) return
  const { data } = await editionPresetsService.save(name, settings.value)
  presets.value.push(data)
  presets.value.sort((a, b) => a.name.localeCompare(b.name))
  selectedPreset.value = data
  showSaveInput.value = false
  saveInputName.value = ''
}

async function updatePreset() {
  if (!selectedPreset.value || !settings.value) return
  const { data } = await editionPresetsService.update(selectedPreset.value.id, { settings: settings.value })
  const idx = presets.value.findIndex(p => p.id === data.id)
  if (idx !== -1) presets.value[idx] = data
  selectedPreset.value = data
}

async function deletePreset() {
  if (!selectedPreset.value) return
  await editionPresetsService.remove(selectedPreset.value.id)
  presets.value = presets.value.filter(p => p.id !== selectedPreset.value!.id)
  selectedPreset.value = null
}

// Sync store → local à l'init (une seule fois)
let skipNextSave = false
watch(() => edition.settings, (s) => {
  if (s && !settings.value) {
    skipNextSave = true
    settings.value = JSON.parse(JSON.stringify(s))
  }
}, { immediate: true })

const TEMPLATES = [
  { key: 'pocket',       label: 'Roman poche',   sub: '11 × 18 cm' },
  { key: 'large_format', label: 'Grand format',  sub: '15 × 21 cm' },
  { key: 'a4',           label: 'Format A4',     sub: '21 × 29.7 cm' },
  { key: 'custom',       label: 'Personnalisé',  sub: 'Dimensions libres' },
]

const TEMPLATE_DEFAULTS: Record<string, Partial<EditionSettings['page']>> = {
  pocket:       { width: 11, height: 18, margin_top: 20, margin_bottom: 20, margin_inner: 25, margin_outer: 20, gutter: 5 },
  large_format: { width: 15, height: 21, margin_top: 22, margin_bottom: 22, margin_inner: 28, margin_outer: 22, gutter: 7 },
  a4:           { width: 21, height: 29.7, margin_top: 25, margin_bottom: 25, margin_inner: 30, margin_outer: 25, gutter: 8 },
}

const SEPARATOR_OPTIONS = [
  { key: 'stars',  label: '***' },
  { key: 'dash',   label: '—' },
  { key: 'none',   label: 'Aucun' },
  { key: 'custom', label: 'Personnalisé' },
]

const CHAPTER_START_OPTIONS = [
  { key: 'any',  label: 'Page quelconque' },
  { key: 'odd',  label: 'Page impaire (belle page)' },
  { key: 'even', label: 'Page paire' },
]

const HEADER_FIELDS = [
  { key: 'book_title',    label: 'Titre du roman' },
  { key: 'chapter_title', label: 'Titre du chapitre' },
  { key: 'arc_title',     label: 'Titre de l\'arc' },
  { key: 'author_name',   label: 'Nom de l\'auteur' },
  { key: 'page_number',   label: 'Numéro de page' },
  { key: 'none',          label: 'Aucun' },
]

onMounted(async () => {
  await edition.load(props.projectId)
  loading.value = false
  loadPresets()
})

function scheduleSave() {
  if (!settings.value) return
  if (skipNextSave) { skipNextSave = false; return }
  if (saveTimer) clearTimeout(saveTimer)
  saveTimer = setTimeout(() => {
    if (settings.value) edition.patchSettings(settings.value)
  }, 600)
}

function applyTemplate(key: string) {
  if (!settings.value) return
  settings.value.template = key as EditionSettings['template']
  if (key !== 'custom' && TEMPLATE_DEFAULTS[key]) {
    settings.value.page = { ...settings.value.page, ...TEMPLATE_DEFAULTS[key] }
  }
  scheduleSave()
}

watch(settings, scheduleSave, { deep: true })
</script>

<template>
  <div class="flex flex-col h-full border-l border-gray-300 dark:border-gray-700">
    <!-- ── Préréglages ── -->
    <div class="shrink-0 border-b border-gray-300 dark:border-gray-700 px-2 py-1.5">
      <!-- Verrouillé premium -->
      <div v-if="presetsLocked" class="flex items-center gap-1.5 text-amber-500 dark:text-amber-400 text-xs">
        <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
        </svg>
        <span>Préréglages — fonctionnalité premium</span>
      </div>

      <template v-else>
        <!-- Saisie du nom pour sauvegarder -->
        <div v-if="showSaveInput" class="flex items-center gap-1">
          <input
            v-model="saveInputName"
            class="field-input flex-1 py-1"
            placeholder="Nom du préréglage…"
            autofocus
            @keydown.enter="saveNewPreset"
            @keydown.escape="showSaveInput = false; saveInputName = ''"
          />
          <button class="px-2 py-1 text-xs rounded bg-brand-600 text-white hover:bg-brand-700 transition-colors" @click="saveNewPreset">OK</button>
          <button class="px-2 py-1 text-xs rounded text-gray-500 hover:text-gray-700 dark:hover:text-gray-200 transition-colors" @click="showSaveInput = false; saveInputName = ''">✕</button>
        </div>

        <!-- Barre normale -->
        <div v-else class="flex items-center gap-1">
          <!-- Sélecteur de préréglage -->
          <select
            class="field-input flex-1 py-1 text-xs"
            :value="selectedPreset?.id ?? ''"
            @change="(e) => { const p = presets.find(x => x.id === +((e.target as HTMLSelectElement).value)); if (p) applyPreset(p) }"
          >
            <option value="" disabled>Préréglages…</option>
            <option v-for="p in presets" :key="p.id" :value="p.id">{{ p.name }}</option>
          </select>

          <!-- Mettre à jour le préréglage sélectionné -->
          <button
            v-if="selectedPreset"
            title="Mettre à jour ce préréglage"
            class="p-1 rounded text-gray-500 hover:text-brand-600 dark:hover:text-brand-400 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
            @click="updatePreset"
          >
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
            </svg>
          </button>

          <!-- Sauvegarder nouveau -->
          <button
            title="Sauvegarder comme nouveau préréglage"
            class="p-1 rounded text-gray-500 hover:text-brand-600 dark:hover:text-brand-400 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
            @click="showSaveInput = true; saveInputName = selectedPreset?.name ?? ''"
          >
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
          </button>

          <!-- Supprimer -->
          <button
            v-if="selectedPreset"
            title="Supprimer ce préréglage"
            class="p-1 rounded text-gray-500 hover:text-red-500 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
            @click="deletePreset"
          >
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
            </svg>
          </button>
        </div>
      </template>
    </div>

    <!-- Onglets -->
    <div class="flex shrink-0 border-b border-gray-300 dark:border-gray-700 overflow-x-auto">
      <button
        v-for="tab in ([
          { key: 'layout',     label: 'Mise en page' },
          { key: 'typography', label: 'Typographie' },
          { key: 'structure',  label: 'Structure' },
          { key: 'headers',    label: 'En-têtes' },
        ] as { key: Tab; label: string }[])"
        :key="tab.key"
        class="shrink-0 px-3 py-2 text-xs font-medium border-b-2 transition-colors whitespace-nowrap"
        :class="activeTab === tab.key
          ? 'border-brand-500 text-brand-600 dark:text-brand-400'
          : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200'"
        @click="activeTab = tab.key"
      >
        {{ tab.label }}
      </button>
    </div>

    <div v-if="loading" class="flex-1 flex items-center justify-center">
      <svg class="w-4 h-4 animate-spin text-gray-400" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
      </svg>
    </div>

    <template v-else-if="settings">
      <!-- ══ Mise en page ══ -->
      <div v-if="activeTab === 'layout'" class="flex-1 overflow-y-auto p-3 space-y-4">
        <!-- Gabarit -->
        <div>
          <p class="field-label">Gabarit</p>
          <div class="grid grid-cols-2 gap-1.5">
            <button
              v-for="t in TEMPLATES"
              :key="t.key"
              class="text-left px-2.5 py-2 rounded-md border text-xs transition-colors"
              :class="settings.template === t.key
                ? 'border-brand-500 bg-brand-50 dark:bg-brand-900/20 text-brand-700 dark:text-brand-300'
                : 'border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400 hover:border-gray-300 dark:hover:border-gray-600'"
              @click="applyTemplate(t.key)"
            >
              <span class="font-medium block">{{ t.label }}</span>
              <span class="text-gray-400 dark:text-gray-500">{{ t.sub }}</span>
            </button>
          </div>
        </div>

        <!-- Dimensions (custom uniquement) -->
        <div v-if="settings.template === 'custom'">
          <p class="field-label">Dimensions (cm)</p>
          <div class="grid grid-cols-2 gap-2">
            <div>
              <label class="field-sublabel">Largeur</label>
              <input v-model.number="settings.page.width" type="number" step="0.5" min="5" max="30" class="field-input" />
            </div>
            <div>
              <label class="field-sublabel">Hauteur</label>
              <input v-model.number="settings.page.height" type="number" step="0.5" min="10" max="45" class="field-input" />
            </div>
          </div>
        </div>

        <!-- Marges -->
        <div>
          <p class="field-label">Marges (mm)</p>
          <div class="grid grid-cols-2 gap-2">
            <div>
              <label class="field-sublabel">Haut</label>
              <input v-model.number="settings.page.margin_top" type="number" step="1" min="5" max="60" class="field-input" />
            </div>
            <div>
              <label class="field-sublabel">Bas</label>
              <input v-model.number="settings.page.margin_bottom" type="number" step="1" min="5" max="60" class="field-input" />
            </div>
            <div>
              <label class="field-sublabel">Intérieur</label>
              <input v-model.number="settings.page.margin_inner" type="number" step="1" min="5" max="60" class="field-input" />
            </div>
            <div>
              <label class="field-sublabel">Extérieur</label>
              <input v-model.number="settings.page.margin_outer" type="number" step="1" min="5" max="60" class="field-input" />
            </div>
          </div>
        </div>

        <!-- Gouttière -->
        <div>
          <label class="field-label">Gouttière (mm)</label>
          <input v-model.number="settings.page.gutter" type="number" step="1" min="0" max="20" class="field-input w-24" />
        </div>

        <!-- Interligne -->
        <div>
          <label class="field-label">Interligne</label>
          <input v-model.number="settings.text.line_height" type="number" step="0.05" min="1" max="3" class="field-input w-24" />
        </div>

        <!-- Alignement -->
        <div>
          <p class="field-label">Alignement</p>
          <div class="flex gap-1.5">
            <button
              v-for="opt in [{ key: 'justified', label: 'Justifié' }, { key: 'left', label: 'Fer à gauche' }]"
              :key="opt.key"
              class="flex-1 px-2 py-1.5 rounded-md border text-xs transition-colors"
              :class="settings.text.alignment === opt.key
                ? 'border-brand-500 bg-brand-50 dark:bg-brand-900/20 text-brand-700 dark:text-brand-300'
                : 'border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400 hover:border-gray-300'"
              @click="settings!.text.alignment = opt.key as 'justified' | 'left'"
            >
              {{ opt.label }}
            </button>
          </div>
        </div>
      </div>

      <!-- ══ Typographie ══ -->
      <div v-else-if="activeTab === 'typography'" class="flex-1 overflow-y-auto p-3 space-y-4">
        <!-- Corps de texte -->
        <div>
          <p class="field-label">Corps de texte</p>
          <div class="space-y-2">
            <div>
              <label class="field-sublabel">Police</label>
              <input v-model="settings.text.body_font" type="text" placeholder="Georgia" class="field-input" />
            </div>
            <div>
              <label class="field-sublabel">Taille (pt)</label>
              <input v-model.number="settings.text.body_font_size" type="number" step="0.5" min="8" max="16" class="field-input w-24" />
            </div>
          </div>
        </div>

        <!-- Titres de chapitres -->
        <div>
          <p class="field-label">Titres de chapitres</p>
          <div class="space-y-2">
            <div>
              <label class="field-sublabel">Police (vide = même que corps)</label>
              <input v-model="settings.titles.font" type="text" placeholder="Idem corps" class="field-input" />
            </div>
            <div>
              <label class="field-sublabel">Taille (pt)</label>
              <input v-model.number="settings.titles.size" type="number" step="1" min="10" max="36" class="field-input w-24" />
            </div>
            <div>
              <label class="field-sublabel">Alignement</label>
              <div class="flex gap-1.5">
                <button
                  v-for="opt in [{ k: 'left', l: 'Gauche' }, { k: 'center', l: 'Centré' }, { k: 'right', l: 'Droite' }]"
                  :key="opt.k"
                  class="flex-1 px-2 py-1.5 rounded-md border text-xs transition-colors"
                  :class="settings.titles.alignment === opt.k
                    ? 'border-brand-500 bg-brand-50 dark:bg-brand-900/20 text-brand-700 dark:text-brand-300'
                    : 'border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400 hover:border-gray-300'"
                  @click="settings!.titles.alignment = opt.k as 'left' | 'center' | 'right'"
                >{{ opt.l }}</button>
              </div>
            </div>
          </div>
        </div>

        <!-- Espacement du titre de chapitre -->
        <div>
          <p class="field-label">Espacement du titre</p>
          <div class="grid grid-cols-2 gap-2">
            <div>
              <label class="field-sublabel">Avant (em)</label>
              <input v-model.number="settings.titles.space_before" type="number" step="0.5" min="0" max="10" class="field-input" />
            </div>
            <div>
              <label class="field-sublabel">Après (em)</label>
              <input v-model.number="settings.titles.space_after" type="number" step="0.5" min="0" max="6" class="field-input" />
            </div>
          </div>
        </div>

        <!-- Lettrine -->
        <div>
          <div class="flex items-center justify-between mb-2">
            <p class="field-label mb-0">Lettrine</p>
            <button
              class="relative inline-flex h-5 w-9 rounded-full transition-colors"
              :class="settings.titles.drop_cap ? 'bg-brand-600' : 'bg-gray-300 dark:bg-gray-700'"
              @click="settings!.titles.drop_cap = !settings!.titles.drop_cap"
            >
              <span class="absolute top-0.5 left-0.5 h-4 w-4 rounded-full bg-white shadow transition-transform"
                :class="settings.titles.drop_cap ? 'translate-x-4' : 'translate-x-0'" />
            </button>
          </div>
          <div v-if="settings.titles.drop_cap">
            <label class="field-sublabel">Hauteur (lignes)</label>
            <input v-model.number="settings.titles.drop_cap_lines" type="number" step="1" min="2" max="5" class="field-input w-24" />
          </div>
        </div>
      </div>

      <!-- ══ Structure ══ -->
      <div v-else-if="activeTab === 'structure'" class="flex-1 overflow-y-auto p-3 space-y-4">
        <!-- Numérotation des chapitres -->
        <div>
          <label class="field-label">Numérotation des chapitres</label>
          <select v-model="settings.titles.numbering" class="field-input">
            <option value="none">Aucune</option>
            <option value="arabic">Chiffres arabes (1, 2…)</option>
            <option value="roman">Chiffres romains (I, II…)</option>
            <option value="text">En lettres (Un, Deux…)</option>
          </select>
        </div>

        <!-- Début de chapitre -->
        <div>
          <p class="field-label">Début de chapitre</p>
          <div class="space-y-1">
            <label
              v-for="opt in CHAPTER_START_OPTIONS"
              :key="opt.key"
              class="flex items-center gap-2 px-2 py-1.5 rounded-md cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
            >
              <input
                type="radio"
                :value="opt.key"
                v-model="settings.structure.chapter_start"
                class="accent-brand-500"
              />
              <span class="text-xs text-gray-700 dark:text-gray-200">{{ opt.label }}</span>
            </label>
          </div>
        </div>

        <!-- Page de partie (arc) -->
        <div class="flex items-center justify-between">
          <div>
            <p class="field-label mb-0">Page de partie</p>
            <p class="text-xs text-gray-400 dark:text-gray-500">Chaque arc génère une page de titre</p>
          </div>
          <button
            class="relative inline-flex h-5 w-9 rounded-full transition-colors shrink-0"
            :class="settings.structure.part_page ? 'bg-brand-600' : 'bg-gray-300 dark:bg-gray-700'"
            @click="settings!.structure.part_page = !settings!.structure.part_page"
          >
            <span class="absolute top-0.5 left-0.5 h-4 w-4 rounded-full bg-white shadow transition-transform"
              :class="settings.structure.part_page ? 'translate-x-4' : 'translate-x-0'" />
          </button>
        </div>

        <!-- Séparateur de scène -->
        <div>
          <p class="field-label">Séparateur de scène</p>
          <div class="space-y-1">
            <label
              v-for="opt in SEPARATOR_OPTIONS"
              :key="opt.key"
              class="flex items-center gap-2 px-2 py-1.5 rounded-md cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
            >
              <input
                type="radio"
                :value="opt.key"
                v-model="settings.structure.scene_separator"
                class="accent-brand-500"
              />
              <span class="text-xs text-gray-700 dark:text-gray-200 font-mono">{{ opt.label }}</span>
            </label>
          </div>
          <div v-if="settings.structure.scene_separator === 'custom'" class="mt-2">
            <input
              v-model="settings.structure.scene_separator_custom"
              type="text"
              placeholder="Ex: ~ ou ⁂"
              maxlength="10"
              class="field-input"
            />
          </div>
        </div>

        <!-- Espacement du séparateur -->
        <div v-if="settings.structure.scene_separator !== 'none'">
          <p class="field-label">Espacement du séparateur</p>
          <div class="grid grid-cols-2 gap-2">
            <div>
              <label class="field-sublabel">Avant (em)</label>
              <input v-model.number="settings.structure.separator_space_before" type="number" step="0.25" min="0" max="6" class="field-input" />
            </div>
            <div>
              <label class="field-sublabel">Après (em)</label>
              <input v-model.number="settings.structure.separator_space_after" type="number" step="0.25" min="0" max="6" class="field-input" />
            </div>
          </div>
        </div>
      </div>

      <!-- ══ En-têtes / Pieds ══ -->
      <div v-else-if="activeTab === 'headers'" class="flex-1 overflow-y-auto p-3 space-y-4">
        <!-- Convention : gauche = verso, droite = recto -->
        <p class="text-xs text-gray-400 dark:text-gray-500 italic leading-relaxed">
          Convention éditoriale : page gauche (verso) = titre du roman, page droite (recto) = titre du chapitre.
        </p>

        <!-- Filets -->
        <div class="space-y-2">
          <div class="flex items-center justify-between">
            <p class="text-xs text-gray-700 dark:text-gray-200">Filet sous l'en-tête</p>
            <button
              class="relative inline-flex h-5 w-9 rounded-full transition-colors shrink-0"
              :class="settings.headers.header_rule ? 'bg-brand-600' : 'bg-gray-300 dark:bg-gray-700'"
              @click="settings!.headers.header_rule = !settings!.headers.header_rule"
            >
              <span class="absolute top-0.5 left-0.5 h-4 w-4 rounded-full bg-white shadow transition-transform"
                :class="settings.headers.header_rule ? 'translate-x-4' : 'translate-x-0'" />
            </button>
          </div>
          <div class="flex items-center justify-between">
            <p class="text-xs text-gray-700 dark:text-gray-200">Filet au-dessus du pied de page</p>
            <button
              class="relative inline-flex h-5 w-9 rounded-full transition-colors shrink-0"
              :class="settings.headers.footer_rule ? 'bg-brand-600' : 'bg-gray-300 dark:bg-gray-700'"
              @click="settings!.headers.footer_rule = !settings!.headers.footer_rule"
            >
              <span class="absolute top-0.5 left-0.5 h-4 w-4 rounded-full bg-white shadow transition-transform"
                :class="settings.headers.footer_rule ? 'translate-x-4' : 'translate-x-0'" />
            </button>
          </div>
        </div>

        <!-- Espacement des filets -->
        <div>
          <p class="field-label">Espacement des filets (pt)</p>
          <div class="grid grid-cols-2 gap-2">
            <div>
              <label class="field-sublabel">Avant (texte → filet)</label>
              <input v-model.number="settings.headers.rule_space_before" type="number" step="0.5" min="0" max="20" class="field-input" />
            </div>
            <div>
              <label class="field-sublabel">Après (filet → corps)</label>
              <input v-model.number="settings.headers.rule_space_after" type="number" step="0.5" min="0" max="20" class="field-input" />
            </div>
          </div>
        </div>

        <div>
          <label class="field-label">Page gauche (verso)</label>
          <select v-model="settings.headers.left_field" class="field-input">
            <option v-for="f in HEADER_FIELDS" :key="f.key" :value="f.key">{{ f.label }}</option>
          </select>
        </div>

        <div>
          <label class="field-label">Page droite (recto)</label>
          <select v-model="settings.headers.right_field" class="field-input">
            <option v-for="f in HEADER_FIELDS" :key="f.key" :value="f.key">{{ f.label }}</option>
          </select>
        </div>

        <div>
          <label class="field-label">Position du folio</label>
          <div class="flex gap-1.5">
            <button
              v-for="opt in [{ k: 'center', l: 'Centré' }, { k: 'outer', l: 'Extérieur' }, { k: 'inner', l: 'Intérieur' }]"
              :key="opt.k"
              class="flex-1 px-2 py-1.5 rounded-md border text-xs transition-colors"
              :class="settings.footer.position === opt.k
                ? 'border-brand-500 bg-brand-50 dark:bg-brand-900/20 text-brand-700 dark:text-brand-300'
                : 'border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400 hover:border-gray-300'"
              @click="settings!.footer.position = opt.k as 'center' | 'outer' | 'inner'"
            >
              {{ opt.l }}
            </button>
          </div>
        </div>

        <!-- Affichage du folio par type de page -->
        <div>
          <p class="field-label">Afficher le numéro de page sur…</p>
          <div class="space-y-2">
            <div
              v-for="opt in [
                { key: 'show_on_liminaries', label: 'Pages liminaires (dédicace, préface…)' },
                { key: 'show_on_toc',        label: 'Table des matières' },
                { key: 'show_on_parts',      label: 'Pages de partie (arcs)' },
              ]"
              :key="opt.key"
              class="flex items-center justify-between"
            >
              <p class="text-xs text-gray-700 dark:text-gray-200 leading-tight">{{ opt.label }}</p>
              <button
                class="relative inline-flex h-5 w-9 rounded-full transition-colors shrink-0 ml-2"
                :class="(settings.footer as any)[opt.key] ? 'bg-brand-600' : 'bg-gray-300 dark:bg-gray-700'"
                @click="(settings!.footer as any)[opt.key] = !(settings!.footer as any)[opt.key]"
              >
                <span
                  class="absolute top-0.5 left-0.5 h-4 w-4 rounded-full bg-white shadow transition-transform"
                  :class="(settings.footer as any)[opt.key] ? 'translate-x-4' : 'translate-x-0'"
                />
              </button>
            </div>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>

<style scoped>
@reference "@/assets/main.css";

.field-label {
  @apply text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1.5 block;
}
.field-sublabel {
  @apply text-xs text-gray-500 dark:text-gray-500 mb-1 block;
}
.field-input {
  @apply w-full text-xs rounded-md border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-800 dark:text-gray-200 px-2 py-1.5 focus:outline-none focus:ring-1 focus:ring-brand-500 focus:border-brand-500 transition-colors;
}
</style>
