<script setup lang="ts">
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue'
import { useEditorStore } from '@/stores/editor.store'
import { useAuthStore } from '@/stores/auth.store'
import { useEditionStore } from '@/stores/edition.store'
import { useAppConfigStore } from '@/stores/appConfig.store'
import { paginate, computeMetrics } from '@/composables/useEditionPaginator'
import type { BookPage } from '@/composables/useEditionPaginator'
import EditionBookPage from './EditionBookPage.vue'

const props = defineProps<{ projectId: string }>()

const editor    = useEditorStore()
const auth      = useAuthStore()
const edition   = useEditionStore()
const appConfig = useAppConfigStore()

const exportLocked = computed(() =>
  !!(appConfig.config?.edition_export_is_premium && !auth.user?.effective_premium)
)

// ── Mode d'affichage ──────────────────────────────────────────
type ViewMode = 'double' | 'single' | 'thumbs'
const viewMode = ref<ViewMode>('double')

// ── Données depuis le store partagé ───────────────────────────
const settings    = computed(() => edition.settings)
const documents   = computed(() => edition.documents)
const docContents = computed(() => edition.documentContents)
const loading     = computed(() => edition.loading)

onMounted(() => edition.load(props.projectId))

// ── Métriques et pages ────────────────────────────────────────
const metrics = computed(() => settings.value ? computeMetrics(settings.value) : null)

const pages = computed(() => {
  if (!settings.value || !editor.arcs.length && !documents.value.some(d => d.is_enabled)) return []

  return paginate({
    settings: settings.value,
    arcs: editor.arcs,
    documents: documents.value,
    bookTitle: editor.currentProject?.title ?? 'Mon roman',
    authorName: auth.user?.name ?? '',
    documentContents: docContents.value,
  })
})

// ── Spreads (paires de pages pour le mode double) ─────────────
const spreads = computed(() => {
  const ps = pages.value
  const result: { left: typeof ps[number] | null; right: typeof ps[number] | null }[] = []
  for (let i = 0; i < ps.length; i += 2) {
    result.push({ left: ps[i] ?? null, right: ps[i + 1] ?? null })
  }
  return result
})

// ── Scale dynamique selon le conteneur ────────────────────────
const containerRef = ref<HTMLElement | null>(null)
const scale = ref(1)

function updateScale() {
  if (!containerRef.value || !metrics.value) return
  const available = containerRef.value.clientWidth - 48 // padding
  let target: number
  if (viewMode.value === 'double') {
    target = available / (metrics.value.pageWidthPx * 2 + 24) // 2 pages + gap
  } else if (viewMode.value === 'thumbs') {
    target = 160 / metrics.value.pageWidthPx // vignette de 160px
  } else {
    target = available / metrics.value.pageWidthPx
  }
  scale.value = Math.min(1, Math.max(0.1, target))
}

const ro = new ResizeObserver(updateScale)
onMounted(() => {
  if (containerRef.value) ro.observe(containerRef.value)
  updateScale()
})
onUnmounted(() => ro.disconnect())
watch([viewMode, metrics], updateScale)

// ── Export ────────────────────────────────────────────────────
const showExportModal = ref(false)
const exporting       = ref(false)

const exportOptions = ref({
  liminaries: true,
  toc:        true,
  parts:      true,
  annexes:    true,
})

const printPages = ref<BookPage[] | null>(null)

function buildPrintPages(): BookPage[] {
  return pages.value.filter(p => {
    if (p.kind === 'auto_title' || p.kind === 'auto_copyright') return true
    if (p.kind === 'blank') return true
    if (p.kind === 'liminary'  && !exportOptions.value.liminaries) return false
    if (p.kind === 'auto_toc'  && !exportOptions.value.toc)        return false
    if (p.kind === 'part'      && !exportOptions.value.parts)       return false
    if (p.kind === 'annex'     && !exportOptions.value.annexes)     return false
    return true
  })
}

async function exportPDF() {
  if (!metrics.value || !settings.value) return
  exporting.value = true

  printPages.value = buildPrintPages()

  const style = document.createElement('style')
  style.id = 'papyrus-page-size'
  style.textContent = `@page { size: ${settings.value.page.width}cm ${settings.value.page.height}cm; margin: 0; }`
  document.head.appendChild(style)

  await nextTick()
  window.print()

  style.remove()
  printPages.value = null
  exporting.value  = false
  showExportModal.value = false
}
</script>

<template>
  <div ref="containerRef" class="flex flex-col h-full overflow-hidden bg-gray-200 dark:bg-gray-900">

    <!-- ── Barre d'outils preview ── -->
    <div class="shrink-0 flex items-center justify-between gap-3 px-4 py-2 bg-gray-100 dark:bg-gray-800 border-b border-gray-300 dark:border-gray-700">
      <!-- Modes d'affichage -->
      <div class="flex gap-1 bg-white dark:bg-gray-700 rounded-md p-0.5">
        <button
          v-for="m in ([
            { key: 'single', icon: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z' },
            { key: 'double', icon: 'M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2' },
            { key: 'thumbs', icon: 'M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z' },
          ] as { key: ViewMode; icon: string }[])"
          :key="m.key"
          class="p-1.5 rounded transition-colors"
          :class="viewMode === m.key
            ? 'bg-brand-100 dark:bg-brand-900/40 text-brand-600 dark:text-brand-400'
            : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200'"
          :title="m.key === 'single' ? 'Page unique' : m.key === 'double' ? 'Double page' : 'Vignettes'"
          @click="viewMode = m.key"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" :d="m.icon" />
          </svg>
        </button>
      </div>

      <!-- Infos -->
      <span class="text-xs text-gray-400 dark:text-gray-500">
        {{ pages.length }} page{{ pages.length !== 1 ? 's' : '' }}
      </span>

      <!-- Scale -->
      <span class="text-xs text-gray-400 dark:text-gray-500 tabular-nums">
        {{ Math.round(scale * 100) }}%
      </span>

      <!-- Bouton export -->
      <button
        class="ml-auto flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-medium transition-colors disabled:opacity-50"
        :class="exportLocked
          ? 'text-amber-500 dark:text-amber-400 cursor-default opacity-80'
          : 'bg-brand-600 hover:bg-brand-700 text-white'"
        :disabled="!pages.length || exportLocked"
        :title="exportLocked ? 'Fonctionnalité premium' : 'Exporter'"
        @click="!exportLocked && (showExportModal = true)"
      >
        <svg v-if="exportLocked" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
        </svg>
        <svg v-else class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
        </svg>
        Exporter
      </button>
    </div>

    <!-- ── Modal export ── -->
    <Teleport to="body">
      <div
        v-if="showExportModal"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
        @click.self="showExportModal = false"
      >
        <div class="bg-white dark:bg-gray-900 rounded-xl shadow-2xl w-full max-w-sm mx-4 overflow-hidden">
          <!-- Header -->
          <div class="flex items-center justify-between px-5 py-4 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Exporter le livre</h2>
            <button class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200" @click="showExportModal = false">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
              </svg>
            </button>
          </div>

          <div class="px-5 py-4 space-y-5">
            <!-- Format -->
            <div>
              <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2">Format</p>
              <div class="grid grid-cols-3 gap-2">
                <button class="flex flex-col items-center gap-1 px-2 py-3 rounded-lg border-2 border-brand-500 bg-brand-50 dark:bg-brand-900/20 text-brand-700 dark:text-brand-300 text-xs font-medium">
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                  </svg>
                  PDF
                </button>
                <button class="flex flex-col items-center gap-1 px-2 py-3 rounded-lg border border-gray-200 dark:border-gray-700 text-gray-400 text-xs cursor-not-allowed" disabled title="Bientôt disponible">
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                  </svg>
                  EPUB
                  <span class="text-gray-300 dark:text-gray-600" style="font-size:0.65rem">bientôt</span>
                </button>
                <button class="flex flex-col items-center gap-1 px-2 py-3 rounded-lg border border-gray-200 dark:border-gray-700 text-gray-400 text-xs cursor-not-allowed" disabled title="Bientôt disponible">
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                  </svg>
                  DOCX
                  <span class="text-gray-300 dark:text-gray-600" style="font-size:0.65rem">bientôt</span>
                </button>
              </div>
            </div>

            <!-- Contenu -->
            <div>
              <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2">Contenu</p>
              <div class="space-y-2">
                <label
                  v-for="opt in [
                    { key: 'liminaries', label: 'Pages liminaires', sub: 'Dédicace, préface…' },
                    { key: 'toc',        label: 'Table des matières', sub: '' },
                    { key: 'parts',      label: 'Pages de partie', sub: 'Titres d\'arcs' },
                    { key: 'annexes',    label: 'Annexes', sub: 'Glossaire, index…' },
                  ]"
                  :key="opt.key"
                  class="flex items-center gap-3 cursor-pointer"
                >
                  <input
                    type="checkbox"
                    :checked="(exportOptions as any)[opt.key]"
                    class="accent-brand-500 w-4 h-4 shrink-0"
                    @change="(exportOptions as any)[opt.key] = !((exportOptions as any)[opt.key])"
                  />
                  <div>
                    <p class="text-xs font-medium text-gray-800 dark:text-gray-200">{{ opt.label }}</p>
                    <p v-if="opt.sub" class="text-xs text-gray-400 dark:text-gray-500">{{ opt.sub }}</p>
                  </div>
                </label>
              </div>
            </div>

            <!-- Format de page -->
            <div class="flex items-center gap-2 px-3 py-2 rounded-lg bg-gray-50 dark:bg-gray-800 text-xs text-gray-500 dark:text-gray-400">
              <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
              <span>Format {{ settings?.page.width ?? '?' }} × {{ settings?.page.height ?? '?' }} cm — configurez "Enregistrer sous PDF" dans la boîte d'impression.</span>
            </div>
          </div>

          <!-- Footer -->
          <div class="flex justify-end gap-2 px-5 py-3 border-t border-gray-200 dark:border-gray-700">
            <button
              class="px-3 py-1.5 text-xs rounded-md text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
              @click="showExportModal = false"
            >Annuler</button>
            <button
              class="px-4 py-1.5 text-xs font-medium rounded-md bg-brand-600 hover:bg-brand-700 text-white transition-colors disabled:opacity-50 flex items-center gap-1.5"
              :disabled="exporting"
              @click="exportPDF"
            >
              <svg v-if="exporting" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
              </svg>
              Générer le PDF
            </button>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- ── Zone d'impression (invisible à l'écran) ── -->
    <Teleport to="body">
      <div v-if="printPages && metrics" class="papyrus-print-area">
        <EditionBookPage
          v-for="page in printPages"
          :key="page.id"
          :page="page"
          :metrics="metrics"
          class="papyrus-print-page"
        />
      </div>
    </Teleport>

    <!-- ── Chargement ── -->
    <div v-if="loading" class="flex-1 flex items-center justify-center">
      <svg class="w-5 h-5 animate-spin text-gray-400" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
      </svg>
    </div>

    <!-- ── Prévisualisation ── -->
    <div v-else-if="metrics" class="flex-1 overflow-y-auto overflow-x-hidden">

      <!-- Mode double page -->
      <div v-if="viewMode === 'double'" class="flex flex-col items-center gap-6 py-6">
        <div
          v-for="(spread, si) in spreads"
          :key="si"
          class="flex gap-0 items-start"
          :style="{ transform: `scale(${scale})`, transformOrigin: 'top center', marginBottom: `${(metrics.pageHeightPx * scale) - metrics.pageHeightPx}px` }"
        >
          <!-- Page gauche (verso) -->
          <div class="shadow-[2px_0_6px_rgba(0,0,0,0.1)]">
            <EditionBookPage v-if="spread.left" :page="spread.left" :metrics="metrics" />
            <div v-else class="bg-gray-100" :style="{ width: `${metrics.pageWidthPx}px`, height: `${metrics.pageHeightPx}px` }" />
          </div>
          <!-- Page droite (recto) -->
          <div class="shadow-[-2px_0_6px_rgba(0,0,0,0.1)]">
            <EditionBookPage v-if="spread.right" :page="spread.right" :metrics="metrics" />
            <div v-else class="bg-gray-100" :style="{ width: `${metrics.pageWidthPx}px`, height: `${metrics.pageHeightPx}px` }" />
          </div>
        </div>
      </div>

      <!-- Mode page unique -->
      <div v-else-if="viewMode === 'single'" class="flex flex-col items-center gap-6 py-6">
        <div
          v-for="page in pages"
          :key="page.id"
          :style="{ transform: `scale(${scale})`, transformOrigin: 'top center', marginBottom: `${(metrics.pageHeightPx * scale) - metrics.pageHeightPx}px` }"
        >
          <EditionBookPage :page="page" :metrics="metrics" />
        </div>
      </div>

      <!-- Mode vignettes -->
      <div v-else class="p-4 grid gap-3" style="grid-template-columns: repeat(auto-fill, minmax(140px, 1fr))">
        <div v-for="page in pages" :key="page.id" class="flex flex-col items-center gap-1">
          <div
            class="overflow-hidden shadow"
            :style="{ width: `${metrics.pageWidthPx * scale}px`, height: `${metrics.pageHeightPx * scale}px` }"
          >
            <div :style="{ transform: `scale(${scale})`, transformOrigin: 'top left', width: `${metrics.pageWidthPx}px`, height: `${metrics.pageHeightPx}px` }">
              <EditionBookPage :page="page" :metrics="metrics" :is-thumb="true" />
            </div>
          </div>
          <span class="text-xs text-gray-400">{{ page.displayNum || '—' }}</span>
        </div>
      </div>

    </div>

    <!-- État vide -->
    <div v-else class="flex-1 flex items-center justify-center text-gray-400 dark:text-gray-600 text-sm">
      Chargement de la prévisualisation…
    </div>
  </div>
</template>

<style>
/* Zone d'impression — invisible à l'écran */
.papyrus-print-area { display: none !important; }

@media print {
  /* Cacher toute l'appli */
  body > *:not(.papyrus-print-area) { display: none !important; }

  /* Afficher uniquement la zone d'impression */
  .papyrus-print-area {
    display: block !important;
  }

  /* Chaque page = une page imprimée */
  .papyrus-print-page {
    page-break-after: always;
    break-after: page;
    box-shadow: none !important;
    overflow: visible !important;
  }

  .papyrus-print-page:last-child {
    page-break-after: avoid;
    break-after: avoid;
  }
}
</style>
