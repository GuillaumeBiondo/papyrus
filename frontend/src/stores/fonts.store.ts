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
  // Supprimer les anciens tags
  document.querySelectorAll('[data-gf-papyrus]').forEach(el => el.remove())

  const googleFonts = fonts.filter(f => f.google_font_slug && f.enabled)
  if (!googleFonts.length) return

  // Un <link> par police pour qu'une URL invalide n'en bloque pas d'autres
  for (const font of googleFonts) {
    const slug = encodeURIComponent(font.google_font_slug)
    const link = document.createElement('link')
    link.setAttribute('data-gf-papyrus', font.id.toString())
    link.rel         = 'stylesheet'
    link.crossOrigin = 'anonymous'
    link.href        = `https://fonts.googleapis.com/css2?family=${slug}:ital,wght@0,400;0,600;1,400;1,600&display=swap`
    link.onerror     = () => console.warn(`[fonts] Impossible de charger "${font.name}" (slug: ${font.google_font_slug})`)
    document.head.appendChild(link)
  }
}
