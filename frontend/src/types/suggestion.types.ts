export interface SuggestionChange {
  from: number
  to: number
  originalText: string
  suggestedText: string
}

export interface SuggestionBatch {
  id: string
  label: string
  source: string
  changes: SuggestionChange[]
  status: 'pending' | 'accepted' | 'rejected'
  createdAt: Date
}

export interface ProposeSuggestionsOptions {
  label?: string
  source?: string
}
