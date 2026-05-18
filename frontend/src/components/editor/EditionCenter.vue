<script setup lang="ts">
import { ref, watch, onBeforeUnmount } from 'vue'
import { useEditor, EditorContent } from '@tiptap/vue-3'
import StarterKit from '@tiptap/starter-kit'
import Placeholder from '@tiptap/extension-placeholder'
import type { EditionDocumentEntry } from '@/types'
import { editionService } from '@/services/edition.service'
import { useEditionStore } from '@/stores/edition.store'

const props = defineProps<{
  document: EditionDocumentEntry | null
  projectTitle?: string
}>()

const emit = defineEmits<{ close: [] }>()

const LABELS: Record<string, string> = {
  dedication:      'Dédicace',
  epigraph:        'Épigraphe',
  foreword:        'Avant-propos',
  author_note:     'Note de l\'auteur',
  acknowledgements:'Remerciements',
  about_author:    'À propos de l\'auteur',
  glossary:        'Glossaire',
  character_index: 'Index des personnages',
  timeline:        'Chronologie',
  genealogy:       'Arbre généalogique',
  map:             'Carte du monde',
  bestiary:        'Bestiaire / Panthéon',
  notes:           'Notes & références',
  bibliography:    'Bibliographie',
  reading_club:    'Questions de lecture',
  playlist:        'Playlist',
  preview:         'Extrait de la suite',
}

// ── Titre éditable ────────────────────────────────────────────
const titleEl = ref<HTMLElement | null>(null)
let titleSaveTimer: ReturnType<typeof setTimeout> | null = null

function onTitleBlur() {
  if (!titleEl.value || !props.document?.id) return
  const title = titleEl.value.innerText.trim() || null
  if (titleSaveTimer) clearTimeout(titleSaveTimer)
  titleSaveTimer = setTimeout(() => {
    if (props.document?.id) editionService.updateDocument(props.document.id, { title })
  }, 400)
}

// ── TipTap ────────────────────────────────────────────────────
let editorReady = false
let contentSaveTimer: ReturnType<typeof setTimeout> | null = null

const editor = useEditor({
  content: '',
  extensions: [
    StarterKit.configure({ heading: { levels: [1, 2, 3] } }),
    Placeholder.configure({ placeholder: 'Commence à rédiger…' }),
  ],
  editorProps: {
    attributes: {
      class: 'prose prose-gray dark:prose-invert max-w-none focus:outline-none',
    },
  },
  onCreate() {
    setTimeout(() => { editorReady = true }, 0)
  },
  onUpdate({ editor }) {
    if (!editorReady || !props.document?.id) return
    const html = editor.getHTML()
    // Mise à jour du store immédiate pour que la preview réagisse
    if (props.document.id) editionStore.updateDocumentContent(props.document.id, html)
    if (contentSaveTimer) clearTimeout(contentSaveTimer)
    contentSaveTimer = setTimeout(() => {
      if (props.document?.id) editionService.updateDocument(props.document.id, { content: html })
    }, 800)
  },
})

const editionStore   = useEditionStore()
const loadingContent = ref(false)

// Charge le contenu depuis le store (déjà chargé) ou l'API en fallback
watch(
  () => props.document,
  async (doc) => {
    if (!editor.value) return

    if (!doc || !doc.id) {
      editor.value.commands.setContent('', false)
      return
    }

    // Contenu déjà dans le store ?
    const cached = editionStore.documentContents[doc.id]
    if (cached !== undefined) {
      editor.value.commands.setContent(cached, false)
      editorReady = false
      setTimeout(() => { editorReady = true }, 0)
      return
    }

    loadingContent.value = true
    try {
      const { data } = await editionService.getDocument(doc.id)
      const content = data.content ?? ''
      editionStore.updateDocumentContent(doc.id, content)
      editor.value.commands.setContent(content, false)
      editorReady = false
      setTimeout(() => { editorReady = true }, 0)
    } finally {
      loadingContent.value = false
    }
  },
)

onBeforeUnmount(() => {
  editor.value?.destroy()
  if (titleSaveTimer) clearTimeout(titleSaveTimer)
  if (contentSaveTimer) clearTimeout(contentSaveTimer)
})
</script>

<template>
  <!-- Aucun document sélectionné -->
  <div
    v-if="!document"
    class="flex-1 flex flex-col items-center justify-center gap-4 text-center px-8"
    style="background: var(--editor-bg)"
  >
    <div class="absolute top-0 left-0 right-0 flex justify-center pt-10 pointer-events-none select-none overflow-hidden" aria-hidden="true">
      <span
        class="font-bold tracking-tighter leading-none text-center px-8 text-gray-200 dark:text-white/[0.04]"
        style="font-size: clamp(2rem, 8vw, 5.5rem)"
      >{{ projectTitle ?? 'Papyrus' }}</span>
    </div>
    <p class="text-sm text-gray-300 dark:text-white/[0.08] pointer-events-none select-none">
      Sélectionne un document dans la sidebar pour l'éditer.
    </p>
  </div>

  <!-- Éditeur de document -->
  <div v-else class="flex-1 overflow-y-auto px-4 md:px-8 py-6 editor-viewport" style="background: var(--editor-bg)">
    <!-- Retour à la preview + label -->
    <div class="flex items-center gap-2 mb-3">
      <button
        class="flex items-center gap-1 text-xs text-gray-400 dark:text-gray-500 hover:text-brand-500 dark:hover:text-brand-400 transition-colors"
        @click="emit('close')"
      >
        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
        Prévisualisation
      </button>
      <span class="text-gray-300 dark:text-gray-600">·</span>
      <p class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wide">
        {{ LABELS[document.type] ?? document.label }}
      </p>
    </div>

    <!-- Titre personnalisable -->
    <h1
      ref="titleEl"
      class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-6 outline-none empty:before:content-[attr(data-placeholder)] empty:before:text-gray-400 dark:empty:before:text-gray-600"
      contenteditable
      data-placeholder="Titre personnalisé (optionnel)…"
      @blur="onTitleBlur"
    >{{ document.title || '' }}</h1>

    <!-- Éditeur TipTap -->
    <EditorContent :editor="editor" />
  </div>
</template>
