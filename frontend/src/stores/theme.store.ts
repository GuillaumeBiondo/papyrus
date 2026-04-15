import { defineStore } from 'pinia'
import { computed, ref } from 'vue'

type Theme = 'light' | 'dark' | 'system'

export const useThemeStore = defineStore('theme', () => {
  const theme = ref<Theme>((localStorage.getItem('theme') as Theme) ?? 'system')

  const applied = computed<'light' | 'dark'>(() => {
    if (theme.value === 'system') {
      return matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'
    }
    return theme.value
  })

  function setTheme(value: Theme) {
    theme.value = value
    localStorage.setItem('theme', value)
    document.documentElement.classList.toggle('dark', applied.value === 'dark')
  }

  function cycleTheme() {
    const next: Theme = theme.value === 'light' ? 'dark' : theme.value === 'dark' ? 'system' : 'light'
    setTheme(next)
  }

  matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
    if (theme.value === 'system') {
      document.documentElement.classList.toggle('dark', applied.value === 'dark')
    }
  })

  return { theme, applied, setTheme, cycleTheme }
})
