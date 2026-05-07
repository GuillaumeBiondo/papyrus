<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { useAuthStore } from '@/stores/auth.store'
import { useThemeStore } from '@/stores/theme.store'
import { ACCENT_PALETTES, FONT_FAMILIES, FONT_SIZES } from '@/utils/accentColors'
import type { UserPreferences } from '@/types'

const auth  = useAuthStore()
const theme = useThemeStore()

// ── Section active ────────────────────────────────────────────
const activeSection = ref<'theme' | 'appearance' | 'cards' | 'attributes'>('theme')

// ── Thème ─────────────────────────────────────────────────────
const themeOptions = [
  { key: 'light',  label: 'Clair' },
  { key: 'dark',   label: 'Sombre' },
  { key: 'system', label: 'Système' },
] as const

// ── Apparence ─────────────────────────────────────────────────
const appearanceMode = ref<'light' | 'dark'>('light')

const prefs = computed(() => auth.preferences)

function getAppearance(mode: 'light' | 'dark') {
  return prefs.value[mode] ?? {}
}

function accentFor(mode: 'light' | 'dark') {
  return getAppearance(mode).accentColor ?? 'indigo'
}
function fontFor(mode: 'light' | 'dark') {
  return getAppearance(mode).fontFamily ?? 'system'
}
function fontSizeFor(mode: 'light' | 'dark') {
  return getAppearance(mode).fontSize ?? 17
}
function editorBgFor(mode: 'light' | 'dark') {
  return getAppearance(mode).editorBg ?? (mode === 'dark' ? '#0c0b18' : '#f5f4f1')
}

const saving = ref(false)
const saveSuccess = ref(false)

async function saveAppearance(mode: 'light' | 'dark', patch: Partial<UserPreferences['light']>) {
  saving.value = true
  saveSuccess.value = false
  try {
    const current = getAppearance(mode)
    await auth.updatePreferences({ [mode]: { ...current, ...patch } })
    theme.applyPreferences(auth.preferences as any)
    saveSuccess.value = true
    setTimeout(() => { saveSuccess.value = false }, 2000)
  } finally {
    saving.value = false
  }
}

// ── Affichage des fiches ──────────────────────────────────────
const cardDisplay = computed(() => prefs.value.cardDisplay ?? 'dot')

async function setCardDisplay(v: 'dot' | 'avatar') {
  await auth.updatePreferences({ cardDisplay: v })
}

// ── Attributs par défaut ──────────────────────────────────────
const CARD_TYPES = [
  { key: 'personnage', label: 'Personnage' },
  { key: 'lieu',       label: 'Lieu'       },
  { key: 'evenement',  label: 'Événement'  },
  { key: 'objet',      label: 'Objet'      },
  { key: 'theme',      label: 'Thème'      },
]

const activeAttrType = ref('personnage')
const newAttrKey     = ref('')

const defaultAttributes = computed(() =>
  (prefs.value.defaultAttributes ?? {}) as Record<string, string[]>
)

function attrsForType(type: string) {
  return defaultAttributes.value[type] ?? []
}

async function addAttrKey() {
  const key = newAttrKey.value.trim()
  if (!key) return
  const type    = activeAttrType.value
  const current = attrsForType(type)
  if (current.includes(key)) { newAttrKey.value = ''; return }
  await auth.updatePreferences({
    defaultAttributes: { ...defaultAttributes.value, [type]: [...current, key] },
  })
  newAttrKey.value = ''
}

async function removeAttrKey(type: string, key: string) {
  const current = attrsForType(type).filter(k => k !== key)
  await auth.updatePreferences({
    defaultAttributes: { ...defaultAttributes.value, [type]: current },
  })
}

// Sync appearanceMode with current theme
watch(() => theme.applied, (t) => { appearanceMode.value = t }, { immediate: true })
</script>

<template>
  <div class="flex h-full overflow-hidden">

    <!-- Sidebar sections -->
    <aside class="w-44 shrink-0 border-r border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50 p-3 space-y-0.5">
      <p class="text-[10px] font-semibold uppercase tracking-widest text-gray-400 px-2 mb-2 mt-1">Paramètres</p>
      <button
        v-for="{ key, label, icon } in [
          { key: 'theme',      label: 'Thème',          icon: '🌓' },
          { key: 'appearance', label: 'Apparence',       icon: '🎨' },
          { key: 'cards',      label: 'Fiches',          icon: '🃏' },
          { key: 'attributes', label: 'Attrs par défaut', icon: '📋' },
        ]"
        :key="key"
        class="w-full text-left text-sm px-3 py-2 rounded-lg transition-colors flex items-center gap-2"
        :class="activeSection === key
          ? 'bg-white dark:bg-gray-800 text-brand-600 dark:text-brand-400 font-medium shadow-sm'
          : 'text-gray-600 dark:text-gray-400 hover:bg-white/60 dark:hover:bg-gray-800/50'"
        @click="activeSection = key as typeof activeSection"
      >
        <span>{{ icon }}</span>{{ label }}
      </button>
    </aside>

    <!-- Contenu -->
    <div class="flex-1 overflow-y-auto p-6 max-w-xl">

      <!-- ── THÈME ── -->
      <section v-if="activeSection === 'theme'">
        <h2 class="text-base font-semibold text-gray-900 dark:text-gray-100 mb-1">Thème</h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-5">
          Choisissez l'apparence générale de l'interface.
        </p>

        <div class="flex gap-3">
          <button
            v-for="opt in themeOptions"
            :key="opt.key"
            class="flex-1 py-3 px-4 rounded-xl border-2 text-sm font-medium transition-all"
            :class="theme.theme === opt.key
              ? 'border-brand-500 bg-brand-50 dark:bg-brand-900/30 text-brand-700 dark:text-brand-300'
              : 'border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400 hover:border-gray-300 dark:hover:border-gray-600'"
            @click="theme.setTheme(opt.key)"
          >
            <span class="block text-xl mb-1">
              {{ opt.key === 'light' ? '☀️' : opt.key === 'dark' ? '🌙' : '💻' }}
            </span>
            {{ opt.label }}
          </button>
        </div>
      </section>

      <!-- ── APPARENCE ── -->
      <section v-else-if="activeSection === 'appearance'">
        <h2 class="text-base font-semibold text-gray-900 dark:text-gray-100 mb-1">Apparence</h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-5">
          Personnalisez l'éditeur pour chaque mode.
        </p>

        <!-- Mode tabs -->
        <div class="flex gap-1 mb-6 bg-gray-100 dark:bg-gray-800 rounded-lg p-1 w-fit">
          <button
            v-for="m in ['light', 'dark'] as const"
            :key="m"
            class="px-4 py-1.5 text-sm rounded-md transition-colors"
            :class="appearanceMode === m
              ? 'bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm font-medium'
              : 'text-gray-500 hover:text-gray-700 dark:hover:text-gray-300'"
            @click="appearanceMode = m"
          >{{ m === 'light' ? '☀️ Clair' : '🌙 Sombre' }}</button>
        </div>

        <div class="space-y-6">

          <!-- Couleur d'accent -->
          <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-3">
              Couleur d'accentuation
            </p>
            <div class="flex flex-wrap gap-2">
              <button
                v-for="p in ACCENT_PALETTES"
                :key="p.key"
                :title="p.name"
                class="w-8 h-8 rounded-full transition-transform hover:scale-110 ring-offset-2 dark:ring-offset-gray-900"
                :class="accentFor(appearanceMode) === p.key ? 'ring-2 ring-gray-400 scale-110' : ''"
                :style="{ background: p.base }"
                @click="saveAppearance(appearanceMode, { accentColor: p.key })"
              />
            </div>
            <p class="text-xs text-gray-400 mt-2">
              {{ ACCENT_PALETTES.find(p => p.key === accentFor(appearanceMode))?.name }}
            </p>
          </div>

          <!-- Police -->
          <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-3">
              Police de l'éditeur
            </p>
            <div class="space-y-2">
              <button
                v-for="f in FONT_FAMILIES"
                :key="f.key"
                class="w-full text-left px-4 py-2.5 rounded-lg border transition-colors flex items-center justify-between"
                :class="fontFor(appearanceMode) === f.key
                  ? 'border-brand-400 bg-brand-50 dark:bg-brand-900/20 text-brand-700 dark:text-brand-300'
                  : 'border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:border-gray-300'"
                @click="saveAppearance(appearanceMode, { fontFamily: f.key })"
              >
                <span :style="{ fontFamily: f.css }">{{ f.name }}</span>
                <span :style="{ fontFamily: f.css }" class="text-sm text-gray-400">
                  Exemple de texte
                </span>
              </button>
            </div>
          </div>

          <!-- Taille de police -->
          <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-3">
              Taille de police
            </p>
            <div class="flex gap-2">
              <button
                v-for="s in FONT_SIZES"
                :key="s.value"
                class="flex-1 py-2 rounded-lg border text-center transition-colors"
                :class="fontSizeFor(appearanceMode) === s.value
                  ? 'border-brand-400 bg-brand-50 dark:bg-brand-900/20 text-brand-700 dark:text-brand-300 font-medium'
                  : 'border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400 hover:border-gray-300'"
                @click="saveAppearance(appearanceMode, { fontSize: s.value })"
              >
                <span :style="{ fontSize: s.value + 'px', lineHeight: '1' }">A</span>
                <span class="block text-xs text-gray-400 mt-0.5">{{ s.label }}</span>
              </button>
            </div>
          </div>

          <!-- Couleur de fond de l'éditeur -->
          <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-3">
              Fond de l'éditeur
            </p>
            <div class="flex items-center gap-3">
              <div
                class="w-10 h-10 rounded-lg border border-gray-300 dark:border-gray-600 shrink-0"
                :style="{ background: editorBgFor(appearanceMode) }"
              />
              <input
                type="color"
                :value="editorBgFor(appearanceMode)"
                class="w-10 h-10 rounded-lg cursor-pointer border border-gray-300 dark:border-gray-600 p-0.5 bg-white dark:bg-gray-800"
                @change="e => saveAppearance(appearanceMode, { editorBg: (e.target as HTMLInputElement).value })"
              />
              <span class="text-sm text-gray-500 font-mono">{{ editorBgFor(appearanceMode) }}</span>
            </div>
          </div>

          <Transition name="fade">
            <p v-if="saveSuccess" class="text-xs text-green-600 dark:text-green-400 font-medium">
              Modifications sauvegardées.
            </p>
          </Transition>
        </div>
      </section>

      <!-- ── FICHES ── -->
      <section v-else-if="activeSection === 'cards'">
        <h2 class="text-base font-semibold text-gray-900 dark:text-gray-100 mb-1">Fiches</h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-5">
          Personnalisez l'affichage dans la liste des fiches.
        </p>

        <div class="space-y-3">
          <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
            Affichage de la liste
          </p>

          <button
            class="w-full flex items-center gap-4 p-4 rounded-xl border-2 transition-all text-left"
            :class="cardDisplay === 'dot'
              ? 'border-brand-400 bg-brand-50 dark:bg-brand-900/20'
              : 'border-gray-200 dark:border-gray-700 hover:border-gray-300'"
            @click="setCardDisplay('dot')"
          >
            <div class="flex items-center gap-2">
              <span class="w-2.5 h-2.5 rounded-full bg-brand-500 shrink-0" />
              <span class="text-sm text-gray-700 dark:text-gray-300">Nom de la fiche</span>
            </div>
            <div class="ml-auto text-sm font-medium"
                 :class="cardDisplay === 'dot' ? 'text-brand-600 dark:text-brand-400' : 'text-gray-400'">
              Pastille
            </div>
          </button>

          <button
            class="w-full flex items-center gap-4 p-4 rounded-xl border-2 transition-all text-left"
            :class="cardDisplay === 'avatar'
              ? 'border-brand-400 bg-brand-50 dark:bg-brand-900/20'
              : 'border-gray-200 dark:border-gray-700 hover:border-gray-300'"
            @click="setCardDisplay('avatar')"
          >
            <div class="flex items-center gap-2">
              <div
                class="w-7 h-7 rounded-full bg-brand-500 flex items-center justify-center text-white text-[10px] font-semibold shrink-0"
                style="outline: 2px solid #6D5FE6; outline-offset: 2px"
              >NF</div>
              <span class="text-sm text-gray-700 dark:text-gray-300">Nom de la fiche</span>
            </div>
            <div class="ml-auto text-sm font-medium"
                 :class="cardDisplay === 'avatar' ? 'text-brand-600 dark:text-brand-400' : 'text-gray-400'">
              Avatar
            </div>
          </button>

          <p class="text-xs text-gray-400">
            En mode Avatar, si une image est définie sur la fiche, elle remplace les initiales.
            La bordure reprend la couleur du type.
          </p>
        </div>
      </section>

      <!-- ── ATTRIBUTS PAR DÉFAUT ── -->
      <section v-else-if="activeSection === 'attributes'">
        <h2 class="text-base font-semibold text-gray-900 dark:text-gray-100 mb-1">
          Attributs par défaut
        </h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-5">
          Ces attributs seront automatiquement créés (vides) lors de la création d'une nouvelle fiche.
        </p>

        <!-- Tabs types -->
        <div class="flex flex-wrap gap-1 mb-5">
          <button
            v-for="t in CARD_TYPES"
            :key="t.key"
            class="text-sm px-3 py-1.5 rounded-lg border transition-colors"
            :class="activeAttrType === t.key
              ? 'border-brand-400 bg-brand-50 dark:bg-brand-900/20 text-brand-700 dark:text-brand-300 font-medium'
              : 'border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400 hover:border-gray-300'"
            @click="activeAttrType = t.key"
          >{{ t.label }}</button>
        </div>

        <!-- Liste attributs -->
        <div class="space-y-2 mb-3">
          <div
            v-for="key in attrsForType(activeAttrType)"
            :key="key"
            class="flex items-center justify-between px-3 py-2 rounded-lg
                   bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700"
          >
            <span class="text-sm text-gray-700 dark:text-gray-300">{{ key }}</span>
            <button
              class="text-gray-400 hover:text-red-500 transition-colors"
              @click="removeAttrKey(activeAttrType, key)"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
              </svg>
            </button>
          </div>
          <p
            v-if="!attrsForType(activeAttrType).length"
            class="text-sm text-gray-400 py-2"
          >Aucun attribut par défaut pour ce type.</p>
        </div>

        <!-- Ajouter -->
        <div class="flex gap-2">
          <input
            v-model="newAttrKey"
            type="text"
            placeholder="Nom de l'attribut (ex: Âge, Métier…)"
            class="flex-1 text-sm rounded-lg border border-gray-300 dark:border-gray-600
                   bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200
                   px-3 py-2 focus:outline-none focus:ring-1 focus:ring-brand-500"
            @keyup.enter="addAttrKey"
          />
          <button
            class="px-4 py-2 text-sm bg-brand-600 hover:bg-brand-700 text-white rounded-lg transition-colors"
            @click="addAttrKey"
          >Ajouter</button>
        </div>
      </section>

    </div>
  </div>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.3s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
