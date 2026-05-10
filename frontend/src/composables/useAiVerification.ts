import { onMounted, ref } from 'vue'
import type { Editor } from '@tiptap/vue-3'
import type { Node } from '@tiptap/pm/model'
import { aiService } from '@/services/ai.service'
import { useSuggestionService } from '@/composables/useSuggestionService'
import type { AiVerification } from '@/types'

/**
 * Builds a flat character-to-position map from all text nodes in the document.
 * This allows matching a plain-text substring back to ProseMirror positions,
 * even when that substring spans multiple inline nodes.
 */
function buildPosMap(doc: Node): { text: string; posMap: number[] } {
  const posMap: number[] = []
  let text = ''

  doc.descendants((node, pos) => {
    if (!node.isText) return
    for (let i = 0; i < node.text!.length; i++) {
      posMap.push(pos + i)
      text += node.text![i]
    }
  })

  return { text, posMap }
}

/**
 * Finds the first occurrence of `searchText` in `text` at or after `startFrom` (text offset).
 * Returns ProseMirror {from, to} positions, or null if not found.
 */
function findInDocument(
  doc: Node,
  searchText: string,
  startFromPos = 0,
): { from: number; to: number } | null {
  const { text, posMap } = buildPosMap(doc)

  // Find the text-offset index corresponding to the start ProseMirror position
  let startTextIdx = 0
  if (startFromPos > 0) {
    startTextIdx = posMap.findIndex(p => p >= startFromPos)
    if (startTextIdx === -1) startTextIdx = 0
  }

  const searchIn = text.slice(startTextIdx)
  const idx = searchIn.indexOf(searchText)
  if (idx === -1) return null

  const absIdx = startTextIdx + idx
  const endIdx = absIdx + searchText.length - 1

  if (absIdx >= posMap.length || endIdx >= posMap.length) return null

  return {
    from: posMap[absIdx]!,
    to: posMap[endIdx]! + 1,
  }
}

export function useAiVerification(getEditor: () => Editor | null | undefined) {
  const { propose } = useSuggestionService()

  const verifications = ref<AiVerification[]>([])
  const verifyOpen    = ref(false)

  // Extra input state
  const pendingVerification = ref<AiVerification | null>(null)
  const extraInput          = ref('')
  const extraInputOpen      = ref(false)

  // Selection snapshot taken at the moment the button is clicked
  // (before the extra-input modal steals focus and clears selection)
  const selectionSnapshot = ref<{ from: number; to: number; text: string } | null>(null)

  const running = ref(false)
  const runError = ref('')

  onMounted(async () => {
    try {
      const { verifications: list } = await aiService.getVerifications()
      verifications.value = list
    } catch {
      // silent: toolbar just won't show verifications
    }
  })

  function toggleVerifyOpen() {
    verifyOpen.value = !verifyOpen.value
  }

  function closeVerifyOpen() {
    verifyOpen.value = false
  }

  /**
   * Snapshot the current selection from the editor.
   * Must be called before any modal opens (which would clear the selection).
   */
  function snapshotSelection(): { from: number; to: number; text: string } | null {
    const editor = getEditor()
    if (!editor) return null
    const { from, to, empty } = editor.state.selection
    if (empty) return null
    const text = editor.state.doc.textBetween(from, to, '\n')
    return { from, to, text }
  }

  function startVerification(v: AiVerification) {
    verifyOpen.value = false
    runError.value = ''

    if (v.has_extra_input) {
      // Snapshot selection NOW before the modal opens and blurs the editor
      selectionSnapshot.value = snapshotSelection()
      pendingVerification.value = v
      extraInput.value = ''
      extraInputOpen.value = true
    } else {
      // No extra input — snapshot selection and run immediately
      selectionSnapshot.value = snapshotSelection()
      void executeVerification(v, undefined)
    }
  }

  function cancelExtraInput() {
    extraInputOpen.value = false
    pendingVerification.value = null
    selectionSnapshot.value = null
    extraInput.value = ''
  }

  async function submitExtraInput() {
    if (!pendingVerification.value) return
    const v = pendingVerification.value
    const input = extraInput.value.trim() || undefined
    extraInputOpen.value = false
    pendingVerification.value = null
    await executeVerification(v, input)
  }

  async function executeVerification(v: AiVerification, extra: string | undefined) {
    const editor = getEditor()
    if (!editor) return

    running.value = true
    runError.value = ''

    try {
      let text: string
      let searchStartPos = 0

      const useSelection =
        (v.target === 'selection' || v.target === 'both') && selectionSnapshot.value !== null

      if (useSelection && selectionSnapshot.value) {
        text = selectionSnapshot.value.text
        searchStartPos = selectionSnapshot.value.from
      } else {
        text = editor.state.doc.textBetween(0, editor.state.doc.content.size, '\n')
        searchStartPos = 0
      }

      selectionSnapshot.value = null

      const { changes } = await aiService.verify(v.id, text, extra)

      if (!changes.length) return

      const suggestionChanges = changes
        .map(c => {
          const match = findInDocument(editor.state.doc, c.originalText, searchStartPos)
          if (!match) return null
          return { ...match, originalText: c.originalText, suggestedText: c.suggestedText }
        })
        .filter((c): c is NonNullable<typeof c> => c !== null)

      if (suggestionChanges.length) {
        propose(suggestionChanges, { label: v.label, source: 'ai-verification' })
      }
    } catch (e: unknown) {
      runError.value = e instanceof Error ? e.message : 'Erreur lors de la vérification.'
    } finally {
      running.value = false
    }
  }

  return {
    verifications,
    verifyOpen,
    toggleVerifyOpen,
    closeVerifyOpen,
    startVerification,
    pendingVerification,
    extraInput,
    extraInputOpen,
    cancelExtraInput,
    submitExtraInput,
    running,
    runError,
  }
}
