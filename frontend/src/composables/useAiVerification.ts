import { onMounted, ref } from 'vue'
import type { Editor } from '@tiptap/vue-3'
import type { Node } from '@tiptap/pm/model'
import { aiService } from '@/services/ai.service'
import { cardsService } from '@/services/cards.service'
import { useSuggestionService } from '@/composables/useSuggestionService'
import type { AiVerification, Card } from '@/types'

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

function normalizeForSearch(s: string): string {
  return s
    .replace(/\n/g, '')           // strip paragraph separators
    .replace(/[‘’]/g, "'") // typographic ' → straight '
    .replace(/[“”]/g, '"') // typographic " → straight "
    .replace(/\s+/g, ' ')         // collapse whitespace
    .trim()
}

function findInDocument(
  doc: Node,
  searchText: string,
  startFromPos = 0,
): { from: number; to: number } | null {
  const { text, posMap } = buildPosMap(doc)

  let startTextIdx = 0
  if (startFromPos > 0) {
    startTextIdx = posMap.findIndex(p => p >= startFromPos)
    if (startTextIdx === -1) startTextIdx = 0
  }

  // Try exact match first, then normalized fallback
  const normalized = normalizeForSearch(searchText)
  const normalizedDoc = normalizeForSearch(text)

  let idx = text.slice(startTextIdx).indexOf(searchText)
  let baseIdx = startTextIdx
  let matchText = searchText

  if (idx === -1) {
    // Fallback: normalized search on the full posMap text
    const normalizedDocSlice = normalizedDoc.slice(startTextIdx)
    idx = normalizedDocSlice.indexOf(normalized)
    baseIdx = startTextIdx
    matchText = normalized

    if (idx === -1 && startTextIdx > 0) {
      // Wrap around: search from beginning
      idx = normalizedDoc.indexOf(normalized)
      baseIdx = 0
    }
    if (idx === -1) return null

    // The normalized posMap has the same length as the original posMap
    // since we only stripped \n (not present in posMap text) + collapsed spaces
    // So positions are aligned only for single-char normalizations — use raw posMap
    // but re-search in raw text with the normalized pattern not applicable directly.
    // Instead re-search raw text for the original phrase without \n:
    const stripped = searchText.replace(/\n/g, '').replace(/[‘’]/g, "'").replace(/[“”]/g, '"')
    idx = text.slice(baseIdx).indexOf(stripped)
    if (idx === -1) return null
    matchText = stripped
  }

  const absIdx = baseIdx + idx
  const endIdx = absIdx + matchText.length - 1

  if (absIdx >= posMap.length || endIdx >= posMap.length) return null

  return {
    from: posMap[absIdx]!,
    to: posMap[endIdx]! + 1,
  }
}

export function useAiVerification(
  getEditor: () => Editor | null | undefined,
  getProjectId: () => string,
) {
  const { propose } = useSuggestionService()

  const verifications = ref<AiVerification[]>([])
  const verifyOpen    = ref(false)

  const pendingVerification = ref<AiVerification | null>(null)
  const extraInput          = ref('')
  const extraInputOpen      = ref(false)

  const selectionSnapshot = ref<{ from: number; to: number; text: string } | null>(null)
  const cursorPos         = ref(0)

  // Card context state
  const availableCards  = ref<Record<string, Card[]>>({})
  const selectedCardIds = ref<string[]>([])
  const cardsFetching   = ref(false)

  const running  = ref(false)
  const runError = ref('')

  onMounted(async () => {
    try {
      const { verifications: list } = await aiService.getVerifications()
      verifications.value = list
    } catch {
      // silent
    }
  })

  function toggleVerifyOpen() { verifyOpen.value = !verifyOpen.value }
  function closeVerifyOpen()  { verifyOpen.value = false }

  function snapshotSelection(): { from: number; to: number; text: string } | null {
    const editor = getEditor()
    if (!editor) return null
    const { from, to, empty } = editor.state.selection
    cursorPos.value = from
    if (empty) return null
    const text = editor.state.doc.textBetween(from, to, '\n')
    return { from, to, text }
  }

  async function loadCardsForTypes(types: string[]) {
    const projectId = getProjectId()
    if (!projectId || !types.length) return
    cardsFetching.value = true
    availableCards.value = {}
    await Promise.all(types.map(async (type) => {
      try {
        const result = await cardsService.index(projectId, { type, per_page: 200 })
        availableCards.value[type] = result.data
      } catch { /* ignore */ }
    }))
    cardsFetching.value = false
  }

  function startVerification(v: AiVerification) {
    verifyOpen.value = false
    runError.value = ''

    const needsModal = v.has_extra_input || (v.allowed_card_types?.length ?? 0) > 0

    if (needsModal) {
      selectionSnapshot.value = snapshotSelection()
      pendingVerification.value = v
      extraInput.value = ''
      selectedCardIds.value = []
      availableCards.value = {}
      extraInputOpen.value = true
      if ((v.allowed_card_types?.length ?? 0) > 0) {
        void loadCardsForTypes(v.allowed_card_types!)
      }
    } else {
      selectionSnapshot.value = snapshotSelection()
      void executeVerification(v, undefined)
    }
  }

  function cancelExtraInput() {
    extraInputOpen.value = false
    pendingVerification.value = null
    selectionSnapshot.value = null
    extraInput.value = ''
    selectedCardIds.value = []
    availableCards.value = {}
  }

  async function submitExtraInput() {
    if (!pendingVerification.value) return
    const v     = pendingVerification.value
    const input = extraInput.value.trim() || undefined
    const cards = selectedCardIds.value.length > 0 ? [...selectedCardIds.value] : undefined
    extraInputOpen.value = false
    pendingVerification.value = null
    selectedCardIds.value = []
    availableCards.value = {}
    await executeVerification(v, input, cards)
  }

  function setCardIds(ids: string[]) {
    selectedCardIds.value = ids
  }

  function toggleCard(cardId: string, allowMultiple: boolean) {
    const idx = selectedCardIds.value.indexOf(cardId)
    if (allowMultiple) {
      if (idx === -1) selectedCardIds.value.push(cardId)
      else selectedCardIds.value.splice(idx, 1)
    } else {
      selectedCardIds.value = idx === -1 ? [cardId] : []
    }
  }

  async function executeVerification(v: AiVerification, extra: string | undefined, cardIds?: string[]) {
    const editor = getEditor()
    if (!editor) return

    running.value  = true
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
        // Use cursor position so suggestions appear near where the user was
        searchStartPos = cursorPos.value
      }

      selectionSnapshot.value = null

      const { changes } = await aiService.verify(v.id, text, extra, cardIds)

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
    availableCards,
    selectedCardIds,
    cardsFetching,
    setCardIds,
    toggleCard,
    running,
    runError,
  }
}
