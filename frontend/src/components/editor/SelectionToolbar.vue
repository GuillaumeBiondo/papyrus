<script setup lang="ts">
import { ref, watch, onBeforeUnmount } from 'vue'
import type { Editor } from '@tiptap/core'

const props = defineProps<{
  editor: Editor | undefined
}>()

const emit = defineEmits<{
  annotate: [sel: { from: number; to: number; text: string }]
}>()

const visible = ref(false)
const toolbarStyle = ref<{ top: string; left: string }>({ top: '0px', left: '0px' })

function update() {
  const ed = props.editor
  if (!ed) { visible.value = false; return }

  const { from, to } = ed.state.selection
  if (from === to) { visible.value = false; return }

  const domSel = window.getSelection()
  if (!domSel || domSel.rangeCount === 0) { visible.value = false; return }

  const rect = domSel.getRangeAt(0).getBoundingClientRect()
  if (!rect.width && !rect.height) { visible.value = false; return }

  const W = 360
  const H = 36
  const GAP = 6

  const left = Math.max(8, Math.min(
    rect.left + rect.width / 2 - W / 2,
    window.innerWidth - W - 8,
  ))
  const topAbove = rect.top - H - GAP
  const top = topAbove >= 8 ? topAbove : rect.bottom + GAP

  toolbarStyle.value = { top: `${top}px`, left: `${left}px` }
  visible.value = true
}

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
  ed.chain().focus().insertContentAt({ from, to }, `« ${text} »`).run()
  requestAnimationFrame(update)
}

function onGuillemetsEn(e: MouseEvent) {
  e.preventDefault()
  const ed = props.editor
  if (!ed) return
  const { from, to } = ed.state.selection
  if (from === to) return
  const text = ed.state.doc.textBetween(from, to, ' ')
  ed.chain().focus().insertContentAt({ from, to }, `“${text}”`).run()
  requestAnimationFrame(update)
}

const FONT_SIZE_STEP = 2
const FONT_SIZE_DEFAULT = 16

function getCurrentFontSizePx(): number {
  const attrs = props.editor?.getAttributes('textStyle') as Record<string, string> | undefined
  const raw = attrs?.fontSize
  if (!raw) return FONT_SIZE_DEFAULT
  const match = raw.match(/^(\d+(?:\.\d+)?)(px|em|rem)?$/)
  if (!match) return FONT_SIZE_DEFAULT
  const value = parseFloat(match[1]!)
  const unit = match[2] ?? 'px'
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

let cleanup: (() => void) | null = null

watch(
  () => props.editor,
  (ed) => {
    cleanup?.()
    cleanup = null
    if (!ed) return
    const onSel = () => requestAnimationFrame(update)
    const onBlur = () => { visible.value = false }
    ed.on('selectionUpdate', onSel)
    ed.on('blur', onBlur)
    cleanup = () => {
      ed.off('selectionUpdate', onSel)
      ed.off('blur', onBlur)
    }
  },
  { immediate: true },
)

onBeforeUnmount(() => cleanup?.())
</script>

<template>
  <Teleport to="body">
    <div
      v-if="visible"
      class="fixed z-50 flex items-center gap-0.5 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-1 py-0.5"
      :style="toolbarStyle"
    >
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
      <div class="w-px h-4 bg-gray-200 dark:bg-gray-600 mx-0.5" />
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
    </div>
  </Teleport>
</template>
