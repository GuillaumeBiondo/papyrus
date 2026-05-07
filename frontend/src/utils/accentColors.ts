export interface AccentPalette {
  key: string
  name: string
  base: string
  light: Record<string, string>
  dark: { 300: string; 400: string; 500: string; 600: string; 700: string }
}

export const ACCENT_PALETTES: AccentPalette[] = [
  {
    key: 'indigo',
    name: 'Indigo',
    base: '#6D5FE6',
    light: { 50: '#EEEEFF', 100: '#DDDAFF', 200: '#BCB6FA', 300: '#9B92F5', 400: '#8476EE', 500: '#6D5FE6', 600: '#5446CC', 700: '#4337AA', 800: '#342C89', 900: '#1E196A' },
    dark: { 300: '#CAC3FF', 400: '#B0A5FA', 500: '#9888F2', 600: '#8578EC', 700: '#7264DC' },
  },
  {
    key: 'violet',
    name: 'Violet',
    base: '#8B5CF6',
    light: { 50: '#F5F3FF', 100: '#EDE9FE', 200: '#DDD6FE', 300: '#C4B5FD', 400: '#A78BFA', 500: '#8B5CF6', 600: '#7C3AED', 700: '#6D28D9', 800: '#5B21B6', 900: '#4C1D95' },
    dark: { 300: '#D8B4FE', 400: '#C084FC', 500: '#A855F7', 600: '#9333EA', 700: '#7E22CE' },
  },
  {
    key: 'rose',
    name: 'Rose',
    base: '#F43F5E',
    light: { 50: '#FFF1F2', 100: '#FFE4E6', 200: '#FECDD3', 300: '#FDA4AF', 400: '#FB7185', 500: '#F43F5E', 600: '#E11D48', 700: '#BE123C', 800: '#9F1239', 900: '#881337' },
    dark: { 300: '#FCA5A5', 400: '#F87171', 500: '#EF4444', 600: '#DC2626', 700: '#B91C1C' },
  },
  {
    key: 'orange',
    name: 'Orange',
    base: '#F97316',
    light: { 50: '#FFF7ED', 100: '#FFEDD5', 200: '#FED7AA', 300: '#FDBA74', 400: '#FB923C', 500: '#F97316', 600: '#EA580C', 700: '#C2410C', 800: '#9A3412', 900: '#7C2D12' },
    dark: { 300: '#FCD34D', 400: '#FBBF24', 500: '#F59E0B', 600: '#D97706', 700: '#B45309' },
  },
  {
    key: 'amber',
    name: 'Ambre',
    base: '#F59E0B',
    light: { 50: '#FFFBEB', 100: '#FEF3C7', 200: '#FDE68A', 300: '#FCD34D', 400: '#FBBF24', 500: '#F59E0B', 600: '#D97706', 700: '#B45309', 800: '#92400E', 900: '#78350F' },
    dark: { 300: '#FDE68A', 400: '#FCD34D', 500: '#FBBF24', 600: '#F59E0B', 700: '#D97706' },
  },
  {
    key: 'emerald',
    name: 'Émeraude',
    base: '#10B981',
    light: { 50: '#ECFDF5', 100: '#D1FAE5', 200: '#A7F3D0', 300: '#6EE7B7', 400: '#34D399', 500: '#10B981', 600: '#059669', 700: '#047857', 800: '#065F46', 900: '#064E3B' },
    dark: { 300: '#6EE7B7', 400: '#34D399', 500: '#10B981', 600: '#059669', 700: '#047857' },
  },
  {
    key: 'cyan',
    name: 'Cyan',
    base: '#06B6D4',
    light: { 50: '#ECFEFF', 100: '#CFFAFE', 200: '#A5F3FC', 300: '#67E8F9', 400: '#22D3EE', 500: '#06B6D4', 600: '#0891B2', 700: '#0E7490', 800: '#155E75', 900: '#164E63' },
    dark: { 300: '#67E8F9', 400: '#22D3EE', 500: '#06B6D4', 600: '#0891B2', 700: '#0E7490' },
  },
  {
    key: 'blue',
    name: 'Bleu',
    base: '#3B82F6',
    light: { 50: '#EFF6FF', 100: '#DBEAFE', 200: '#BFDBFE', 300: '#93C5FD', 400: '#60A5FA', 500: '#3B82F6', 600: '#2563EB', 700: '#1D4ED8', 800: '#1E40AF', 900: '#1E3A8A' },
    dark: { 300: '#93C5FD', 400: '#60A5FA', 500: '#3B82F6', 600: '#2563EB', 700: '#1D4ED8' },
  },
  {
    key: 'slate',
    name: 'Ardoise',
    base: '#64748B',
    light: { 50: '#F8FAFC', 100: '#F1F5F9', 200: '#E2E8F0', 300: '#CBD5E1', 400: '#94A3B8', 500: '#64748B', 600: '#475569', 700: '#334155', 800: '#1E293B', 900: '#0F172A' },
    dark: { 300: '#CBD5E1', 400: '#94A3B8', 500: '#64748B', 600: '#475569', 700: '#334155' },
  },
]

export function applyAccent(key: string): void {
  const palette = ACCENT_PALETTES.find(p => p.key === key)
  if (!palette) return

  let el = document.getElementById('accent-override') as HTMLStyleElement | null
  if (!el) {
    el = document.createElement('style')
    el.id = 'accent-override'
    document.head.appendChild(el)
  }

  const lightVars = Object.entries(palette.light)
    .map(([k, v]) => `  --color-brand-${k}: ${v};`)
    .join('\n')

  const darkVars = Object.entries(palette.dark)
    .map(([k, v]) => `  --color-brand-${k}: ${v};`)
    .join('\n')

  el.textContent = `:root {\n${lightVars}\n}\n:root.dark {\n${darkVars}\n}`
}

export const FONT_SIZES = [
  { value: 15, label: 'Petit' },
  { value: 17, label: 'Normal' },
  { value: 19, label: 'Grand' },
  { value: 22, label: 'Très grand' },
]

// cssFamilyResolver est injecté depuis fonts.store au moment de l'appel
export function applyEditorAppearance(
  isDark: boolean,
  prefs: {
    light?: { fontFamily?: number | null; fontSize?: number; editorBg?: string }
    dark?:  { fontFamily?: number | null; fontSize?: number; editorBg?: string }
  },
  cssFamilyResolver: (id: number | null | undefined) => string = () => 'system-ui, sans-serif',
): void {
  const mode = isDark ? prefs.dark : prefs.light
  const ff   = cssFamilyResolver(mode?.fontFamily)
  const fs   = mode?.fontSize ?? 17
  const bg   = mode?.editorBg ?? (isDark ? '#0c0b18' : '#f5f4f1')

  // Validate bg as hex to prevent CSS injection
  const safeBg = /^#[0-9a-fA-F]{6}$/.test(bg) ? bg : (isDark ? '#0c0b18' : '#f5f4f1')

  let el = document.getElementById('editor-appearance') as HTMLStyleElement | null
  if (!el) {
    el = document.createElement('style')
    el.id = 'editor-appearance'
    document.head.appendChild(el)
  }

  el.textContent = [
    ':root {',
    `  --editor-font-family: ${ff};`,
    `  --editor-font-size: ${fs}px;`,
    `  --editor-bg: ${safeBg};`,
    '}',
  ].join('\n')
}
