<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { snapshotsService } from '@/services/snapshots.service'
import type { SceneSnapshot } from '@/types'

const props = defineProps<{ sceneId: string; open: boolean }>()
const emit  = defineEmits<{
  close:    []
  restored: [content: string]
}>()

const snapshots   = ref<SceneSnapshot[]>([])
const loading     = ref(false)
const selected    = ref<SceneSnapshot | null>(null)
const previewText = ref<string | null>(null)
const previewLoading = ref(false)
const restoring   = ref(false)

watch(() => props.open, async (open) => {
  if (!open) { selected.value = null; previewText.value = null; return }
  loading.value = true
  try {
    const { snapshots: list } = await snapshotsService.list(props.sceneId)
    snapshots.value = list
  } finally {
    loading.value = false
  }
})

async function selectSnapshot(snap: SceneSnapshot) {
  if (selected.value?.id === snap.id) return
  selected.value = snap
  previewText.value = null

  if (snap.content) {
    previewText.value = extractText(snap.content)
    return
  }
  previewLoading.value = true
  try {
    const { snapshot } = await snapshotsService.show(props.sceneId, snap.id)
    snap.content = snapshot.content  // cache
    previewText.value = extractText(snapshot.content ?? '')
  } finally {
    previewLoading.value = false
  }
}

async function restore() {
  if (!selected.value) return
  restoring.value = true
  try {
    const { content, snapshot } = await snapshotsService.restore(props.sceneId, selected.value.id)
    snapshots.value.unshift(snapshot)
    emit('restored', content)
    emit('close')
  } finally {
    restoring.value = false
  }
}

// ── Helpers ───────────────────────────────────────────────────
function extractText(content: string | undefined): string {
  return content ?? ''
}

function triggerIcon(t: SceneSnapshot['trigger']) {
  return t === 'manual' ? '📷' : t === 'restore' ? '↩️' : '🔄'
}

function triggerLabel(t: SceneSnapshot['trigger']) {
  return t === 'manual' ? 'Manuel' : t === 'restore' ? 'Restauration' : 'Auto'
}

function formatDate(iso: string) {
  return new Intl.DateTimeFormat('fr-FR', {
    day: '2-digit', month: 'short', year: 'numeric',
    hour: '2-digit', minute: '2-digit',
  }).format(new Date(iso))
}

function formatDelta(d: number) {
  if (d > 0) return `+${d}`
  if (d < 0) return `${d}`
  return '±0'
}

const groupedSnapshots = computed(() => {
  const groups = new Map<string, SceneSnapshot[]>()
  for (const s of snapshots.value) {
    const day = new Date(s.created_at).toLocaleDateString('fr-FR', { day: '2-digit', month: 'long', year: 'numeric' })
    if (!groups.has(day)) groups.set(day, [])
    groups.get(day)!.push(s)
  }
  return [...groups.entries()]
})
</script>

<template>
  <Transition name="drawer">
    <div
      v-if="open"
      class="fixed inset-0 z-40 flex"
      @keyup.escape="$emit('close')"
    >
      <!-- Overlay -->
      <div class="absolute inset-0 bg-black/30 backdrop-blur-sm" @click="$emit('close')" />

      <!-- Panneau -->
      <aside
        class="relative ml-auto w-[min(1050px,92vw)] h-full flex bg-white dark:bg-gray-950
               border-l border-gray-200 dark:border-gray-800 shadow-2xl overflow-hidden"
      >
        <!-- Header -->
        <div class="absolute top-0 left-0 right-0 flex items-center gap-3 px-5 py-3.5
                    border-b border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-950 z-10">
          <span class="text-lg">🎞️</span>
          <h2 class="text-sm font-semibold text-gray-800 dark:text-gray-100">
            Timeline de la scène
          </h2>
          <span class="text-xs text-gray-400 ml-1">{{ snapshots.length }} version{{ snapshots.length > 1 ? 's' : '' }}</span>
          <button
            class="ml-auto text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors"
            @click="$emit('close')"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
          </button>
        </div>

        <!-- Corps : liste + prévisualisation -->
        <div class="flex w-full pt-14 overflow-hidden">

          <!-- Liste des snapshots -->
          <div class="w-56 shrink-0 border-r border-gray-200 dark:border-gray-800 overflow-y-auto">
            <div v-if="loading" class="text-xs text-gray-400 text-center py-8">Chargement…</div>
            <div v-else-if="!snapshots.length" class="text-xs text-gray-400 text-center py-8 px-3">
              Aucun snapshot.
            </div>

            <template v-for="([day, items]) in groupedSnapshots" :key="day">
              <p class="px-3 pt-3 pb-1 text-[10px] font-semibold uppercase tracking-widest text-gray-400">
                {{ day }}
              </p>
              <button
                v-for="snap in items"
                :key="snap.id"
                class="w-full text-left px-3 py-2.5 transition-colors border-l-2"
                :class="selected?.id === snap.id
                  ? 'bg-brand-50 dark:bg-brand-900/20 border-brand-400'
                  : 'border-transparent hover:bg-gray-50 dark:hover:bg-gray-900'"
                @click="selectSnapshot(snap)"
              >
                <div class="flex items-center gap-1.5 mb-0.5">
                  <span class="text-xs">{{ triggerIcon(snap.trigger) }}</span>
                  <span
                    class="text-[10px] font-medium"
                    :class="{
                      'text-brand-600 dark:text-brand-400': snap.trigger === 'manual',
                      'text-gray-400': snap.trigger === 'auto',
                      'text-amber-600 dark:text-amber-400': snap.trigger === 'restore',
                    }"
                  >{{ triggerLabel(snap.trigger) }}</span>
                  <span
                    class="ml-auto text-[10px] font-mono"
                    :class="{
                      'text-emerald-600': snap.word_delta > 0,
                      'text-red-500': snap.word_delta < 0,
                      'text-gray-400': snap.word_delta === 0,
                    }"
                  >{{ formatDelta(snap.word_delta) }}</span>
                </div>
                <p v-if="snap.label" class="text-xs text-gray-700 dark:text-gray-300 truncate">
                  {{ snap.label }}
                </p>
                <p class="text-[10px] text-gray-400">
                  {{ new Date(snap.created_at).toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' }) }}
                  · {{ snap.word_count }} mots
                </p>
              </button>
            </template>
          </div>

          <!-- Prévisualisation -->
          <div class="flex-1 flex flex-col overflow-hidden">
            <div v-if="!selected" class="flex-1 flex items-center justify-center">
              <p class="text-sm text-gray-400">Sélectionne une version pour la prévisualiser.</p>
            </div>

            <template v-else>
              <!-- Meta -->
              <div class="px-5 py-3 border-b border-gray-200 dark:border-gray-800 flex items-start justify-between gap-3">
                <div>
                  <p class="text-sm font-medium text-gray-800 dark:text-gray-100">
                    {{ selected.label || triggerLabel(selected.trigger) }}
                  </p>
                  <p class="text-xs text-gray-400">
                    {{ formatDate(selected.created_at) }} · {{ selected.word_count }} mots
                    <span
                      class="ml-2 font-mono"
                      :class="{
                        'text-emerald-600': selected.word_delta > 0,
                        'text-red-500': selected.word_delta < 0,
                        'text-gray-400': selected.word_delta === 0,
                      }"
                    >{{ formatDelta(selected.word_delta) }}</span>
                  </p>
                </div>
                <button
                  class="shrink-0 flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg
                         bg-amber-500 hover:bg-amber-600 text-white transition-colors disabled:opacity-50"
                  :disabled="restoring"
                  @click="restore"
                >
                  <span>↩️</span>
                  {{ restoring ? 'Restauration…' : 'Restaurer' }}
                </button>
              </div>

              <!-- Texte -->
              <div class="flex-1 overflow-y-auto px-6 py-5">
                <div v-if="previewLoading" class="text-xs text-gray-400">Chargement…</div>
                <div
                  v-else-if="previewText"
                  class="markdown-body text-sm text-gray-700 dark:text-gray-300"
                  style="font-family: var(--editor-font-family, system-ui)"
                  v-html="previewText"
                />
                <p v-else class="text-xs text-gray-400">Aucun contenu.</p>
              </div>
            </template>
          </div>
        </div>
      </aside>
    </div>
  </Transition>
</template>

<style scoped>
.drawer-enter-active, .drawer-leave-active {
  transition: opacity 0.2s ease;
}
.drawer-enter-active aside, .drawer-leave-active aside {
  transition: transform 0.25s ease;
}
.drawer-enter-from, .drawer-leave-to { opacity: 0; }
.drawer-enter-from aside, .drawer-leave-to aside { transform: translateX(100%); }
</style>
