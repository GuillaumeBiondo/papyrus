import { useSuggestionsStore } from '@/stores/suggestions.store'
import type { ProposeSuggestionsOptions, SuggestionChange } from '@/types/suggestion.types'

/**
 * Central service for proposing text modification suggestions.
 * Any feature can call propose() or proposeReplacement() to surface
 * red/green diff markers in the editor for the user to accept or reject.
 */
export function useSuggestionService() {
  const store = useSuggestionsStore()

  function propose(changes: SuggestionChange[], options?: ProposeSuggestionsOptions) {
    return store.addBatch(changes, options)
  }

  function proposeReplacement(
    from: number,
    to: number,
    originalText: string,
    suggestedText: string,
    options?: ProposeSuggestionsOptions,
  ) {
    return store.addBatch([{ from, to, originalText, suggestedText }], options)
  }

  return { propose, proposeReplacement }
}
