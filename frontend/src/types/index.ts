export interface SceneSnapshot {
  id: number
  trigger: 'auto' | 'manual' | 'restore'
  label: string | null
  word_count: number
  word_delta: number
  created_at: string
  content?: string  // chargé à la demande
}

export interface ActivityDay {
  date: string
  logins: number
  words: number
}

export interface ActivityHour {
  day: number    // 0 = dimanche, 1 = lundi … 6 = samedi
  hour: number   // 0-23
  logins: number
  words: number
}

export interface AppConfig {
  snapshot_interval_words: number
}

export interface AvailableFont {
  id: number
  name: string
  google_font_slug: string
  css_family: string
  category: 'serif' | 'sans-serif' | 'monospace'
  enabled: boolean
  sort_order: number
}

export interface AppearancePrefs {
  fontFamily: number | null   // null = système, number = id dans available_fonts
  fontSize: number
  editorBg: string
  accentColor: string
  uiSurface: string           // preset key for dark-mode surface intensity
}

export interface WordGoals {
  project?: number
  arc?: number
  chapter?: number
  scene?: number
}

export interface UserPreferences {
  light: Partial<AppearancePrefs>
  dark: Partial<AppearancePrefs>
  cardDisplay: 'dot' | 'avatar'
  defaultAttributes: Record<string, string[]>
  mantra?: string
  wordGoals?: WordGoals
}

export interface User {
  id: string
  name: string
  email: string
  role: 'user' | 'admin'
  bio: string | null
  avatar_url: string | null
  preferences: Partial<UserPreferences>
  word_goal_defaults: Required<WordGoals>
  last_login_at: string | null
  is_premium: boolean
  premium_override: boolean
  effective_premium: boolean
  currentRole?: 'owner' | 'co_author' | 'beta_reader'
}

export interface ContentType {
  id: string
  name: string
  short_name: string | null
  slug: string
  is_active: boolean
  is_premium: boolean
  type_schema: Record<string, unknown> | null
  description: string | null
  projects_count?: number
  created_at: string
  updated_at: string
}

export interface ProjectContentType {
  id: string
  name: string
  short_name: string | null
  slug: string
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
  maintenance_bypass: boolean
  is_blocked: boolean
  block_reason: string | null
  is_premium: boolean
  premium_override: boolean
  effective_premium: boolean
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

export interface Workshop {
  id: number
  key: string
  label: string
  description: string | null
  is_active: boolean
  is_premium: boolean
  sort_order: number
  created_at: string
  updated_at: string
}

export interface AiEnrichType {
  id: number
  type_key: string
  label: string
  description: string | null
  is_active: boolean
  is_premium: boolean
  system_prompt: string
  sort_order: number
}

export interface MaintenanceStatus {
  active: boolean
  warning: boolean
  start_at: string | null
  end_at: string | null
  message: string
  warning_message: string
  user_exempt: boolean
}

export interface Project {
  id: string
  title: string
  genre: string | null
  color: string | null
  status: 'draft' | 'in_progress' | 'revision' | 'complete'
  target_words: number
  target_scenes: number | null
  word_goal_arc: number | null
  word_goal_chapter: number | null
  word_goal_scene: number | null
  content_type: ProjectContentType | null
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

export interface CardImage {
  id: string
  card_id: string
  original_name: string
  mime_type: string
  size: number
  is_avatar: boolean
  url: string
  created_at: string
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
  images?: CardImage[]
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

export interface AiVerification {
  id: number
  label: string
  description: string | null
  is_active: boolean
  is_premium: boolean
  target: 'selection' | 'all' | 'both'
  has_extra_input: boolean
  extra_input_label: string | null
  extra_input_placeholder: string | null
  pre_prompt: string
  sort_order: number
  allowed_card_types: string[] | null
  allow_multiple_cards: boolean
  created_at?: string
  updated_at?: string
}

export interface AiRevisionStat {
  label: string
  calls: number
  total_chars: number
  avg_chars: number
  avg_changes: number
  est_tokens: number
  last_used_at: string | null
}

export interface AiUserStat {
  user_id: string
  name: string
  email: string
  calls: number
  total_chars: number
  est_tokens: number
  last_used_at: string | null
}

export interface AiDailyStat {
  date: string
  calls: number
}

export interface AiStats {
  totals: {
    calls: number
    total_chars: number
    est_tokens: number
    estimated_cost: number
  }
  by_revision: AiRevisionStat[]
  by_user: AiUserStat[]
  daily: AiDailyStat[]
}

export interface AiEnrichType {
  id: number
  type_key: string
  label: string
  description: string | null
  is_active: boolean
  system_prompt: string
  sort_order: number
  created_at?: string
  updated_at?: string
}

export interface AiChange {
  originalText: string
  suggestedText: string
  explanation?: string
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
