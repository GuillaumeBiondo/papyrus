<script setup lang="ts">
import { watch, onBeforeUnmount } from 'vue'
import { useEditor, EditorContent } from '@tiptap/vue-3'
import StarterKit from '@tiptap/starter-kit'
import Placeholder from '@tiptap/extension-placeholder'
import { Extension } from '@tiptap/core'
import { Plugin, PluginKey } from '@tiptap/pm/state'
import { Decoration, DecorationSet } from '@tiptap/pm/view'
import type { Annotation } from '@/types'

const props = defineProps<{
  content: string
  annotations?: Annotation[]
}>()

const emit = defineEmits<{
  change: [html: string]
  selectionChange: [sel: { from: number; to: number; text: string } | null]
  annotationClick: [annotationId: string]
}>()

// ── Extension décorations colorées ───────────────────────────────
const annotationPluginKey = new PluginKey<DecorationSet>('annotationDecorations')

function buildDecos(doc: Parameters<typeof DecorationSet.create>[0], annotations: Annotation[]) {
  const decos: Decoration[] = []
  for (const ann of annotations) {
    if (ann.type === 'inline' && ann.anchor_start != null && ann.anchor_end != null) {
      const from = ann.anchor_start
      const to = ann.anchor_end
      if (from >= 0 && to > from && to <= doc.content.size) {
        const color = ann.color ?? '#f59e0b'
        decos.push(Decoration.inline(from, to, {
          class: 'annotation-underline',
          'data-annotation-id': ann.id,
          style: `border-bottom: 2px solid ${color}; cursor: pointer;`,
        }))
      }
    }
  }
  return DecorationSet.create(doc, decos)
}

const AnnotationDecorations = Extension.create({
  name: 'annotationDecorations',
  addProseMirrorPlugins() {
    const getAnnotations = () => props.annotations ?? []
    return [
      new Plugin({
        key: annotationPluginKey,
        state: {
          init(_, { doc }) {
            return buildDecos(doc, getAnnotations())
          },
          apply(tr, oldSet) {
            if (tr.docChanged || tr.getMeta(annotationPluginKey)) {
              return buildDecos(tr.doc, getAnnotations())
            }
            return oldSet.map(tr.mapping, tr.doc)
          },
        },
        props: {
          decorations(state) {
            return annotationPluginKey.getState(state)
          },
          handleClick(_view, _pos, event) {
            const target = event.target as HTMLElement
            const span = target.closest('[data-annotation-id]')
            if (span) {
              const id = span.getAttribute('data-annotation-id')
              if (id) {
                emit('annotationClick', id)
                return true
              }
            }
            return false
          },
        },
      }),
    ]
  },
})

// ── Éditeur ──────────────────────────────────────────────────────
const editor = useEditor({
  content: props.content,
  extensions: [
    StarterKit.configure({ heading: { levels: [1, 2, 3] } }),
    Placeholder.configure({ placeholder: 'Commence à écrire…' }),
    AnnotationDecorations,
  ],
  editorProps: {
    attributes: {
      class: 'prose prose-gray dark:prose-invert max-w-none focus:outline-none',
    },
  },
  onUpdate({ editor }) {
    emit('change', editor.getHTML())
  },
  onSelectionUpdate({ editor }) {
    const { from, to } = editor.state.selection
    if (from === to) {
      emit('selectionChange', null)
    } else {
      const text = editor.state.doc.textBetween(from, to, ' ')
      emit('selectionChange', { from, to, text })
    }
  },
})

// Sync quand on change de scène
watch(() => props.content, (val) => {
  if (editor.value && editor.value.getHTML() !== val) {
    // emitUpdate: false — TipTap v3 defaults to true, but switching scenes
    // must not trigger onContentChange and schedule a spurious autosave
    editor.value.commands.setContent(val || '', { emitUpdate: false })
    refreshDecorations()
  }
})

// Rafraîchir quand annotations changent (couleur, nouvel élément, etc.)
watch(() => props.annotations, () => {
  refreshDecorations()
}, { deep: true })

function refreshDecorations() {
  if (!editor.value) return
  const tr = editor.value.state.tr.setMeta(annotationPluginKey, true)
  editor.value.view.dispatch(tr)
}

function focusAnnotation(from: number, to: number) {
  if (!editor.value) return
  editor.value.commands.setTextSelection({ from, to })
  editor.value.commands.scrollIntoView()
  editor.value.commands.focus()
}

onBeforeUnmount(() => editor.value?.destroy())

defineExpose({ editor, focusAnnotation })
</script>

<template>
  <EditorContent :editor="editor" class="h-full" />
</template>

<style>
/* Placeholder TipTap */
.tiptap p.is-editor-empty:first-child::before {
  content: attr(data-placeholder);
  float: left;
  color: #9ca3af;
  pointer-events: none;
  height: 0;
}

/* Styles prose de base sans dépendance @tailwindcss/typography */
.prose {
  color: #111827;
  line-height: 1.75;
  font-size: 1rem;
}
.dark .prose {
  color: #f3f4f6;
}
.prose p { margin-bottom: 1em; }
.prose strong { font-weight: 600; }
.prose em { font-style: italic; }
.prose h1 { font-size: 1.5rem; font-weight: 700; margin-bottom: 0.5em; }
.prose h2 { font-size: 1.25rem; font-weight: 600; margin-bottom: 0.5em; }
.prose h3 { font-size: 1.1rem; font-weight: 600; margin-bottom: 0.5em; }
.prose blockquote {
  border-left: 3px solid #d1d5db;
  padding-left: 1em;
  color: #6b7280;
  font-style: italic;
  margin: 1em 0;
}
.dark .prose blockquote { border-color: #4b5563; color: #9ca3af; }
.prose hr { border-color: #e5e7eb; margin: 2em 0; }
.dark .prose hr { border-color: #374151; }
</style>
