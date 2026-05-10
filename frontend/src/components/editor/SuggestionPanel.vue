<script setup lang="ts">
import { computed, unref } from 'vue'
import type { MaybeRef } from 'vue'
import type { Editor } from '@tiptap/core'
import { useSuggestionsStore } from '@/stores/suggestions.store'

const props = defineProps<{
  editor: MaybeRef<Editor | undefined>
}>()

const resolvedEditor = computed(() => unref(props.editor))

const store = useSuggestionsStore()

const totalPending = computed(() => store.pendingBatches.length)
const currentIndex = computed(() => store.focusedBatchIndex)
const batch = computed(() => store.focusedBatch)
const changeCount = computed(() => batch.value?.changes.length ?? 0)

function acceptBatch() {
  if (!batch.value || !resolvedEditor.value) return
  const currentBatch = batch.value
  const batchId = currentBatch.id

  const sorted = [...currentBatch.changes].sort((a, b) => b.from - a.from)
  const tr = resolvedEditor.value.state.tr

  for (const change of sorted) {
    const from = Math.max(0, Math.min(tr.mapping.map(change.from), tr.doc.content.size))
    const to = Math.max(from, Math.min(tr.mapping.map(change.to), tr.doc.content.size))

    if (change.suggestedText) {
      tr.replaceWith(from, to, resolvedEditor.value.schema.text(change.suggestedText))
    } else if (from < to) {
      tr.delete(from, to)
    }
  }

  resolvedEditor.value.view.dispatch(tr)
  store.markAccepted(batchId)
}

function rejectBatch() {
  if (!batch.value) return
  store.markRejected(batch.value.id)
}

function acceptChange(changeIdx: number) {
  if (!batch.value || !resolvedEditor.value) return
  const currentBatch = batch.value
  const change = currentBatch.changes[changeIdx]
  if (!change) return

  const tr = resolvedEditor.value.state.tr
  const from = Math.max(0, Math.min(change.from, tr.doc.content.size))
  const to = Math.max(from, Math.min(change.to, tr.doc.content.size))

  if (change.suggestedText) {
    tr.replaceWith(from, to, resolvedEditor.value.schema.text(change.suggestedText))
  } else if (from < to) {
    tr.delete(from, to)
  }

  resolvedEditor.value.view.dispatch(tr)

  // Adjust positions of remaining changes after the accepted one
  const offset = (change.suggestedText?.length ?? 0) - (change.to - change.from)
  currentBatch.changes.splice(changeIdx, 1)
  for (let i = changeIdx; i < currentBatch.changes.length; i++) {
    const remaining = currentBatch.changes[i]
    if (remaining) {
      remaining.from += offset
      remaining.to += offset
    }
  }

  if (currentBatch.changes.length === 0) {
    store.markAccepted(currentBatch.id)
  }
}

function rejectChange(changeIdx: number) {
  if (!batch.value) return
  store.removeChange(batch.value.id, changeIdx)
}

function scrollToChange(changeIdx: number) {
  if (!resolvedEditor.value) return
  const change = batch.value?.changes[changeIdx]
  if (!change) return
  try {
    resolvedEditor.value.commands.setTextSelection({ from: change.from, to: change.to })
    resolvedEditor.value.commands.scrollIntoView()
  } catch { /* position out of bounds — ignore */ }
}
</script>

<template>
  <Transition name="suggestion-panel">
    <div
      v-if="batch"
      class="shrink-0 border-t border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 shadow-lg"
    >
      <!-- Header: navigation + actions globales -->
      <div class="flex items-center gap-2 px-4 py-2.5 border-b border-gray-100 dark:border-gray-800">
        <!-- Icône diff -->
        <div class="flex items-center gap-1.5 shrink-0">
          <span class="inline-flex items-center gap-1 text-xs font-medium text-gray-600 dark:text-gray-300">
            <svg class="w-4 h-4 text-brand-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z" />
            </svg>
            {{ batch.label }}
          </span>
          <span class="text-xs text-gray-400">({{ changeCount }} modification{{ changeCount > 1 ? 's' : '' }})</span>
        </div>

        <!-- Navigation entre batches -->
        <div v-if="totalPending > 1" class="flex items-center gap-1 ml-2">
          <button
            class="p-1 rounded text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 disabled:opacity-30 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
            :disabled="currentIndex === 0"
            title="Suggestion précédente"
            @click="store.navigatePrev()"
          >
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
          </button>
          <span class="text-xs text-gray-400 tabular-nums">{{ currentIndex + 1 }}/{{ totalPending }}</span>
          <button
            class="p-1 rounded text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 disabled:opacity-30 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
            :disabled="currentIndex === totalPending - 1"
            title="Suggestion suivante"
            @click="store.navigateNext()"
          >
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
          </button>
        </div>

        <div class="ml-auto flex items-center gap-2">
          <button
            class="px-3 py-1 text-xs rounded-lg border border-red-200 dark:border-red-900 text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors font-medium"
            @click="rejectBatch"
          >
            Tout refuser
          </button>
          <button
            class="px-3 py-1 text-xs rounded-lg bg-green-500 hover:bg-green-600 text-white transition-colors font-medium"
            @click="acceptBatch"
          >
            Tout accepter
          </button>
        </div>
      </div>

      <!-- Liste des changements -->
      <div class="max-h-48 overflow-y-auto">
        <div
          v-for="(change, idx) in batch.changes"
          :key="idx"
          class="flex items-start gap-3 px-4 py-2.5 border-b border-gray-50 dark:border-gray-800/60 last:border-0 hover:bg-gray-50 dark:hover:bg-gray-800/40 cursor-pointer transition-colors group"
          @click="scrollToChange(idx)"
        >
          <!-- Diff visuel inline -->
          <div class="flex-1 min-w-0">
            <div class="flex flex-wrap gap-1 text-xs leading-relaxed">
              <span
                v-if="change.originalText"
                class="suggestion-delete rounded px-1 py-0.5"
              >{{ change.originalText }}</span>
              <span
                v-if="change.suggestedText"
                class="suggestion-insert rounded px-1 py-0.5"
              >{{ change.suggestedText }}</span>
            </div>
          </div>

          <!-- Actions par changement -->
          <div class="flex items-center gap-1 shrink-0 opacity-0 group-hover:opacity-100 transition-opacity">
            <button
              class="p-1 rounded text-red-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors"
              title="Refuser ce changement"
              @click.stop="rejectChange(idx)"
            >
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
              </svg>
            </button>
            <button
              class="p-1 rounded text-green-500 hover:text-green-700 hover:bg-green-50 dark:hover:bg-green-900/20 transition-colors"
              title="Accepter ce changement"
              @click.stop="acceptChange(idx)"
            >
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
              </svg>
            </button>
          </div>
        </div>
      </div>
    </div>
  </Transition>
</template>

<style scoped>
.suggestion-panel-enter-active,
.suggestion-panel-leave-active {
  transition: all 0.2s ease;
}
.suggestion-panel-enter-from,
.suggestion-panel-leave-to {
  opacity: 0;
  transform: translateY(8px);
}
</style>
