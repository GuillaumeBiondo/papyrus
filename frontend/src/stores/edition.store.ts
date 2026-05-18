import { defineStore } from 'pinia'
import { ref } from 'vue'
import { editionService } from '@/services/edition.service'
import type { EditionSettings, EditionDocumentEntry } from '@/types'

export const useEditionStore = defineStore('edition', () => {
  const settings        = ref<EditionSettings | null>(null)
  const documents       = ref<EditionDocumentEntry[]>([])
  const documentContents = ref<Record<number, string>>({})
  const loadedProjectId = ref<string | null>(null)
  const loading         = ref(false)

  async function load(projectId: string) {
    if (loadedProjectId.value === projectId) return
    loading.value = true
    try {
      const [{ data: s }, { data: docs }] = await Promise.all([
        editionService.getSettings(projectId),
        editionService.listDocuments(projectId),
      ])
      loadedProjectId.value = projectId
      settings.value  = s
      documents.value = docs

      // Contenu des docs activés
      await loadEnabledDocumentContents()
    } finally {
      loading.value = false
    }
  }

  async function loadEnabledDocumentContents() {
    const enabled = documents.value.filter(d => d.is_enabled && d.id !== null)
    await Promise.all(
      enabled.map(async d => {
        if (d.id == null || documentContents.value[d.id] !== undefined) return
        try {
          const { data } = await editionService.getDocument(d.id)
          if (data.content) documentContents.value[d.id] = data.content
        } catch { /* silencieux */ }
      }),
    )
  }

  async function patchSettings(patch: Partial<EditionSettings>) {
    if (!settings.value || !loadedProjectId.value) return
    // Mise à jour optimiste
    settings.value = deepMerge(settings.value, patch) as EditionSettings
    await editionService.updateSettings(loadedProjectId.value, patch)
  }

  function updateDocumentContent(id: number, content: string) {
    documentContents.value[id] = content
  }

  function setDocumentEnabled(type: string, id: number | null, enabled: boolean) {
    const doc = documents.value.find(d => d.type === type)
    if (!doc) return
    doc.is_enabled = enabled
    if (id !== null) doc.id = id
    // Charge le contenu si on vient d'activer un doc qui a un id
    if (enabled && id !== null && documentContents.value[id] === undefined) {
      editionService.getDocument(id).then(({ data }) => {
        if (data.content) documentContents.value[id] = data.content
      }).catch(() => {})
    }
  }

  function reset() {
    settings.value        = null
    documents.value       = []
    documentContents.value = {}
    loadedProjectId.value = null
  }

  return {
    settings, documents, documentContents, loading,
    load, patchSettings, updateDocumentContent, setDocumentEnabled, reset,
  }
})

// ── Deep merge utility ─────────────────────────────────────────
function deepMerge(target: Record<string, unknown>, source: Record<string, unknown>): Record<string, unknown> {
  const result = { ...target }
  for (const key of Object.keys(source)) {
    if (source[key] && typeof source[key] === 'object' && !Array.isArray(source[key])
      && target[key] && typeof target[key] === 'object') {
      result[key] = deepMerge(target[key] as Record<string, unknown>, source[key] as Record<string, unknown>)
    } else {
      result[key] = source[key]
    }
  }
  return result
}
