export interface User {
  id: string
  name: string
  email: string
  currentRole?: 'owner' | 'co_author' | 'beta_reader'
}

export interface Project {
  id: string
  title: string
  genre: string | null
  color: string | null
  status: 'draft' | 'in_progress' | 'revision' | 'complete'
  target_words: number
  target_scenes: number | null
  word_count: number
  scene_count: number
  cards_count: number
  last_scene_title: string | null
  owner: User
  members?: UserMember[]
  updated_at: string
  created_at: string
}

export interface UserMember extends User {
  pivot?: { role: 'owner' | 'co_author' | 'beta_reader' }
}

export interface Arc {
  id: string
  project_id: string
  title: string
  order: number
  chapters?: Chapter[]
  updated_at: string
}

export interface Chapter {
  id: string
  arc_id: string
  title: string
  order: number
  scenes?: Scene[]
  updated_at: string
}

export interface Scene {
  id: string
  chapter_id: string
  title: string
  content: string | null
  status: 'idea' | 'draft' | 'revised' | 'final'
  order: number
  word_count: number
  cards?: Card[]
  annotations?: Annotation[]
  notes?: Note[]
  updated_at: string
}

export interface Card {
  id: string
  project_id: string
  type: string
  title: string
  attributes?: CardAttribute[]
  links?: CardLink[]
  keywords?: CardKeyword[]
  notes?: Note[]
  updated_at: string
}

export interface CardAttribute {
  id: string
  key: string
  value: unknown
}

export interface CardLink {
  id: string
  card_id: string
  linked_card: Card
  label: string | null
}

export interface Note {
  id: string
  noteable_type: string
  noteable_id: string
  body: string
  updated_at: string
}

export interface Annotation {
  id: string
  scene_id: string
  user: User
  anchor_start: number | null
  anchor_end: number | null
  body: string
  type: 'inline' | 'global'
  color: string
  cards?: Card[]
  scene?: { id: string; title: string; chapter_title: string | null; arc_title: string | null }
  updated_at: string
}

export interface CardKeyword {
  id: string
  card_id: string
  keyword: string
  case_sensitive: boolean
}

export interface KeywordOccurrence {
  id: string
  card_keyword_id: string
  scene_id: string
  scene?: { id: string; title: string }
  position_start: number
  position_end: number
  context_excerpt: string | null
  computed_at: string
}

export interface NotebookEntry {
  id: string
  title: string | null
  body: string
  project_id: string | null
  updated_at: string
  created_at: string
}

export interface PaginatedResponse<T> {
  data: T[]
  meta: {
    current_page: number
    last_page: number
    per_page: number
    total: number
  }
  links: {
    next: string | null
    prev: string | null
  }
}
