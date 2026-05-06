export interface User {
  id: string
  name: string
  email: string
  role: 'user' | 'admin'
  preferences: Record<string, unknown>
  last_login_at: string | null
  currentRole?: 'owner' | 'co_author' | 'beta_reader'
}

export interface ContentType {
  id: string
  name: string
  slug: string
  is_active: boolean
  type_schema: Record<string, unknown> | null
  description: string | null
  projects_count?: number
  created_at: string
  updated_at: string
}

export interface Changelog {
  id: string
  version: string | null
  title: string
  body: string
  published_at: string | null
  reads_count?: number
  created_at: string
  updated_at: string
}

export interface Setting {
  key: string
  value: unknown
  label: string | null
  group: string
  updated_at: string
}

export interface AdminStats {
  total_users: number
  total_admins: number
  total_projects: number
  total_words: number
  new_users_week: number
  active_users_week: number
}

export interface AdminUser {
  id: string
  name: string
  email: string
  role: 'user' | 'admin'
  last_login_at: string | null
  created_at: string
  preferences: Record<string, unknown>
  projects_count: number
  arcs_count: number
  chapters_count: number
  scenes_count: number
  total_words: number
  avg_words_per_project: number
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
