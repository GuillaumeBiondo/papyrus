<script setup lang="ts">
import { computed, onMounted, onBeforeUnmount, ref, watch } from 'vue'
import type { Editor } from '@tiptap/core'
import { aiService } from '@/services/ai.service'
import { useAuthStore } from '@/stores/auth.store'
import DictionaryDialog from './DictionaryDialog.vue'
import PremiumLock from '@/components/common/PremiumLock.vue'

const auth = useAuthStore()
const isPremium = computed(() => auth.user?.effective_premium ?? false)

const props = defineProps<{
  editor: Editor | undefined
}>()

const emit = defineEmits<{
  annotate: [sel: { from: number; to: number; text: string }]
}>()

// ── Icônes par type_key (fallback si type inconnu) ────────────

const ICON_PATH: Record<string, string> = {
  definition:    'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
  synonymes:     'M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4',
  metaphores:    'M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z',
  champ_lexical: 'M7 20l4-16m2 16l4-16M6 9h14M4 15h14',
  registre:      'M4 6h16M4 10h16M4 14h16M4 18h16',
}
const ICON_DEFAULT = 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253'

function iconPath(typeKey: string): string {
  return ICON_PATH[typeKey] ?? ICON_DEFAULT
}

// ── Position toolbar ──────────────────────────────────────────

const visible      = ref(false)
const toolbarStyle = ref<{ top: string; left: string; width: string }>({ top: '0px', left: '0px', width: '420px' })
const isMobile     = ref(window.innerWidth < 640)

const W = 420

function update() {
  const ed = props.editor
  if (!ed) { visible.value = false; return }

  const { from, to } = ed.state.selection
  if (from === to) { visible.value = false; return }

  const domSel = window.getSelection()
  if (!domSel || domSel.rangeCount === 0) { visible.value = false; return }

  const rect = domSel.getRangeAt(0).getBoundingClientRect()
  if (!rect.width && !rect.height) { visible.value = false; return }

  const GAP    = 6
  const mobile = isMobile.value

  if (mobile) {
    // Sur mobile : toujours en dessous pour ne pas entrer en conflit avec la toolbar système
    const w = window.innerWidth - 16
    toolbarStyle.value = { top: `${rect.bottom + GAP}px`, left: '8px', width: `${w}px` }
  } else {
    const left = Math.max(8, Math.min(
      rect.left + rect.width / 2 - W / 2,
      window.innerWidth - W - 8,
    ))
    const top = rect.bottom + GAP
    toolbarStyle.value = { top: `${top}px`, left: `${left}px`, width: `${W}px` }
  }

  visible.value = true
}

// ── Formatage ─────────────────────────────────────────────────

function onBold(e: MouseEvent) {
  e.preventDefault()
  props.editor?.chain().focus().toggleBold().run()
  requestAnimationFrame(update)
}
function onItalic(e: MouseEvent) {
  e.preventDefault()
  props.editor?.chain().focus().toggleItalic().run()
  requestAnimationFrame(update)
}
function onUnderline(e: MouseEvent) {
  e.preventDefault()
  props.editor?.chain().focus().toggleUnderline().run()
  requestAnimationFrame(update)
}
function onStrike(e: MouseEvent) {
  e.preventDefault()
  props.editor?.chain().focus().toggleStrike().run()
  requestAnimationFrame(update)
}

function onGuillemetsFr(e: MouseEvent) {
  e.preventDefault()
  const ed = props.editor
  if (!ed) return
  const { from, to } = ed.state.selection
  if (from === to) return
  const text = ed.state.doc.textBetween(from, to, ' ')
  ed.chain().focus().insertContentAt({ from, to }, `« ${text} »`).run()
  requestAnimationFrame(update)
}
function onGuillemetsEn(e: MouseEvent) {
  e.preventDefault()
  const ed = props.editor
  if (!ed) return
  const { from, to } = ed.state.selection
  if (from === to) return
  const text = ed.state.doc.textBetween(from, to, ' ')
  ed.chain().focus().insertContentAt({ from, to }, `"${text}"`).run()
  requestAnimationFrame(update)
}

const FONT_SIZE_STEP    = 2
const FONT_SIZE_DEFAULT = 16

function getCurrentFontSizePx(): number {
  const attrs = props.editor?.getAttributes('textStyle') as Record<string, string> | undefined
  const raw   = attrs?.fontSize
  if (!raw) return FONT_SIZE_DEFAULT
  const match = raw.match(/^(\d+(?:\.\d+)?)(px|em|rem)?$/)
  if (!match) return FONT_SIZE_DEFAULT
  const value = parseFloat(match[1]!)
  const unit  = match[2] ?? 'px'
  if (unit === 'em' || unit === 'rem') return Math.round(value * 16)
  return value
}
function onFontSizeIncrease(e: MouseEvent) {
  e.preventDefault()
  const newSize = Math.min(getCurrentFontSizePx() + FONT_SIZE_STEP, 72)
  props.editor?.chain().focus().setFontSize(`${newSize}px`).run()
  requestAnimationFrame(update)
}
function onFontSizeDecrease(e: MouseEvent) {
  e.preventDefault()
  const newSize = Math.max(getCurrentFontSizePx() - FONT_SIZE_STEP, 8)
  props.editor?.chain().focus().setFontSize(`${newSize}px`).run()
  requestAnimationFrame(update)
}

function onAnnotate(e: MouseEvent) {
  e.preventDefault()
  const ed = props.editor
  if (!ed) return
  const { from, to } = ed.state.selection
  if (from === to) return
  const text = ed.state.doc.textBetween(from, to, ' ')
  visible.value = false
  emit('annotate', { from, to, text })
}

// ── Dictionnaire ──────────────────────────────────────────────

type EnrichTypeRef = { id: number; type_key: string; label: string; description: string | null; is_premium: boolean }

const enrichTypes      = ref<EnrichTypeRef[]>([])
const showDictMenu     = ref(false)
const capturedDictText = ref('')

const dictLoading    = ref(false)
const dictItems      = ref<{ text: string; detail: string }[]>([])
const dictError      = ref<string | null>(null)
const dictTypeKey    = ref('')
const dictTypeLabel  = ref('')
const dictQuery      = ref('')
const showDictDialog = ref(false)

const MAX_DICT_CHARS = 80

onMounted(async () => {
  try {
    const { types } = await aiService.getEnrichTypes()
    enrichTypes.value = types
  } catch { /* silencieux si l'API échoue */ }

  const onResize = () => {
    isMobile.value = window.innerWidth < 640
    if (visible.value) requestAnimationFrame(update)
  }
  window.addEventListener('resize', onResize)
  resizeCleanup = () => window.removeEventListener('resize', onResize)
})

function onDictMousedown(e: MouseEvent) {
  e.preventDefault()
  const ed = props.editor
  if (!ed) return
  const { from, to } = ed.state.selection
  if (from === to) return
  capturedDictText.value = ed.state.doc.textBetween(from, to, ' ')
  showDictMenu.value = !showDictMenu.value
  requestAnimationFrame(update)
}

function closeDictMenu(e: MouseEvent) {
  e.preventDefault()
  showDictMenu.value = false
  requestAnimationFrame(update)
}

async function onDictEnrich(e: MouseEvent, type: EnrichTypeRef) {
  e.preventDefault()
  const text = capturedDictText.value
  if (!text) return

  showDictMenu.value = false
  dictTypeKey.value   = type.type_key
  dictTypeLabel.value = type.label
  dictQuery.value     = text
  dictError.value     = null
  dictItems.value     = []

  if (text.length > MAX_DICT_CHARS) {
    dictError.value    = `La sélection est trop longue (${text.length} caractères). Sélectionne un seul mot ou une expression courte pour obtenir un résultat précis.`
    showDictDialog.value = true
    return
  }

  dictLoading.value    = true
  showDictDialog.value = true

  try {
    const result    = await aiService.enrich(type.type_key, text)
    dictItems.value = result.items
  } catch (err: unknown) {
    const axiosErr  = err as { response?: { data?: { message?: string } } }
    dictError.value = axiosErr?.response?.data?.message ?? 'Une erreur est survenue. Réessaie dans un instant.'
  } finally {
    dictLoading.value = false
  }
}

// ── Lifecycle ─────────────────────────────────────────────────

let cleanup:       (() => void) | null = null
let resizeCleanup: (() => void) | null = null

watch(
  () => props.editor,
  (ed) => {
    cleanup?.()
    cleanup = null
    if (!ed) return
    const onSel  = () => requestAnimationFrame(update)
    const onBlur = () => { visible.value = false; showDictMenu.value = false }
    ed.on('selectionUpdate', onSel)
    ed.on('blur', onBlur)
    cleanup = () => {
      ed.off('selectionUpdate', onSel)
      ed.off('blur', onBlur)
    }
  },
  { immediate: true },
)

onBeforeUnmount(() => { cleanup?.(); resizeCleanup?.() })
</script>

<template>
  <Teleport to="body">
    <!-- Backdrop ferme le sous-menu dict sans perdre le focus éditeur -->
    <div
      v-if="showDictMenu"
      class="fixed inset-0"
      style="z-index: 49"
      @mousedown.prevent="closeDictMenu"
    />

    <div
      v-if="visible"
      class="fixed z-50 flex flex-col rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800"
      :style="toolbarStyle"
    >
      <!-- Ligne principale (flex-wrap pour 2 lignes sur mobile) -->
      <div class="flex flex-wrap items-center gap-0.5 px-1 py-0.5">
        <!-- ── Groupe formatage (ligne 1 sur mobile et desktop) ── -->
        <button
          class="w-7 h-7 rounded text-sm font-bold transition-colors"
          :class="editor?.isActive('bold') ? 'bg-gray-200 dark:bg-gray-600 text-gray-900 dark:text-gray-100' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'"
          title="Gras (Ctrl+B)"
          @mousedown="onBold"
        >G</button>
        <button
          class="w-7 h-7 rounded text-sm italic transition-colors"
          :class="editor?.isActive('italic') ? 'bg-gray-200 dark:bg-gray-600 text-gray-900 dark:text-gray-100' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'"
          title="Italique (Ctrl+I)"
          @mousedown="onItalic"
        >I</button>
        <button
          class="w-7 h-7 rounded text-sm underline transition-colors"
          :class="editor?.isActive('underline') ? 'bg-gray-200 dark:bg-gray-600 text-gray-900 dark:text-gray-100' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'"
          title="Souligner (Ctrl+U)"
          @mousedown="onUnderline"
        >S</button>
        <button
          class="w-7 h-7 rounded text-sm line-through transition-colors"
          :class="editor?.isActive('strike') ? 'bg-gray-200 dark:bg-gray-600 text-gray-900 dark:text-gray-100' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'"
          title="Barré"
          @mousedown="onStrike"
        >B</button>
        <div class="w-px h-4 bg-gray-200 dark:bg-gray-600 mx-0.5" />
        <button
          class="w-9 h-7 rounded text-sm transition-colors text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700"
          title="Guillemets français (« »)"
          @mousedown="onGuillemetsFr"
        >«»</button>
        <button
          class="w-9 h-7 rounded text-sm transition-colors text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700"
          title="Guillemets anglais (&ldquo; &rdquo;)"
          @mousedown="onGuillemetsEn"
        >""</button>
        <div class="w-px h-4 bg-gray-200 dark:bg-gray-600 mx-0.5" />
        <button
          class="w-8 h-7 rounded flex items-end justify-center gap-px transition-colors text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700"
          title="Agrandir la police"
          @mousedown="onFontSizeIncrease"
        >
          <span class="text-base font-semibold leading-none">A</span><span class="text-[9px] leading-none mb-0.5">+</span>
        </button>
        <button
          class="w-8 h-7 rounded flex items-end justify-center gap-px transition-colors text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700"
          title="Réduire la police"
          @mousedown="onFontSizeDecrease"
        >
          <span class="text-sm font-semibold leading-none">A</span><span class="text-[9px] leading-none mb-0.5">−</span>
        </button>

        <!-- Saut de ligne : force Annoter + Dico sur la 2e ligne -->
        <div class="basis-full h-0" />
        <button
          class="flex items-center gap-1 px-2 h-7 rounded text-xs font-medium text-brand-600 dark:text-brand-400 hover:bg-brand-50 dark:hover:bg-brand-900/30 transition-colors"
          title="Annoter ce passage"
          @mousedown="onAnnotate"
        >
          <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/>
          </svg>
          Annoter
        </button>

        <!-- Bouton Dictionnaire (masqué si aucun type actif) -->
        <template v-if="enrichTypes.length">
          <div class="w-px h-4 bg-gray-200 dark:bg-gray-600 mx-0.5" />
          <button
            class="flex items-center gap-1 px-2 h-7 rounded text-xs font-medium transition-colors"
            :class="showDictMenu
              ? 'text-white bg-indigo-500 dark:bg-indigo-600'
              : 'text-indigo-600 dark:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-900/30'"
            title="Outils dictionnaire"
            @mousedown="onDictMousedown"
          >
            <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
            </svg>
            Dico
            <svg
              class="w-2.5 h-2.5 opacity-60 transition-transform"
              :class="showDictMenu ? 'rotate-180' : ''"
              fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"
            >
              <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
            </svg>
          </button>
        </template>
      </div>

      <!-- Sous-menu dictionnaire (dynamique) -->
      <div
        v-if="showDictMenu && enrichTypes.length"
        class="flex items-center gap-0.5 px-1 py-0.5 border-t border-gray-100 dark:border-gray-700 flex-wrap"
      >
        <template v-for="(type, idx) in enrichTypes" :key="type.id">
          <div v-if="idx > 0" class="w-px h-4 bg-gray-200 dark:bg-gray-600 mx-0.5" />
          <button
            class="flex items-center gap-1 px-2 h-7 rounded text-xs transition-colors"
            :class="type.is_premium && !isPremium
              ? 'text-amber-500 hover:bg-amber-50 dark:hover:bg-amber-900/20 cursor-not-allowed'
              : 'text-gray-600 dark:text-gray-300 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 hover:text-indigo-600 dark:hover:text-indigo-400'"
            :title="type.is_premium && !isPremium ? 'Fonctionnalité premium' : (type.description ?? type.label)"
            @mousedown="(e) => type.is_premium && !isPremium ? e.preventDefault() : onDictEnrich(e, type)"
          >
            <PremiumLock v-if="type.is_premium && !isPremium" size="xs" />
            <svg v-else class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="iconPath(type.type_key)"/>
            </svg>
            {{ type.label }}
          </button>
        </template>
      </div>
    </div>
  </Teleport>

  <DictionaryDialog
    v-if="showDictDialog"
    :type="dictTypeKey"
    :query="dictQuery"
    :loading="dictLoading"
    :error="dictError"
    :items="dictItems"
    :type-label="dictTypeLabel"
    @close="showDictDialog = false"
  />
</template>
