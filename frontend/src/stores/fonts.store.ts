import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '@/services/api'
import type { AvailableFont } from '@/types'

export const useFontsStore = defineStore('fonts', () => {
  const fonts  = ref<AvailableFont[]>([])
  const loaded = ref(false)

  const byId = computed(() => new Map(fonts.value.map(f => [f.id, f])))

  async function loadFonts() {
    if (loaded.value) return
    try {
      const { data } = await api.get('/fonts')
      fonts.value = data.fonts
      loaded.value = true
      injectGoogleFonts(fonts.value)
    } catch {
      // réseau indisponible — on utilise la police système
    }
  }

  function cssForId(id: number | null | undefined): string {
    if (!id) return 'system-ui, sans-serif'
    return byId.value.get(id)?.css_family ?? 'system-ui, sans-serif'
  }

  return { fonts, loaded, byId, loadFonts, cssForId }
})

function injectGoogleFonts(fonts: AvailableFont[]) {
  document.getElementById('google-fonts-papyrus')?.remove()

  const googleFonts = fonts.filter(f => f.google_font_slug)
  if (!googleFonts.length) return

  const families = googleFonts
    .map(f => `${f.google_font_slug}:ital,wght@0,400;0,600;1,400;1,600`)
    .join('&family=')

  const link = document.createElement('link')
  link.id       = 'google-fonts-papyrus'
  link.rel      = 'stylesheet'
  link.href     = `https://fonts.googleapis.com/css2?family=${families}&display=swap`
  document.head.appendChild(link)
}
