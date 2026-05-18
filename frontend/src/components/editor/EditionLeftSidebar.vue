<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useEditionStore } from '@/stores/edition.store'
import type { EditionDocumentEntry } from '@/types'
import { editionService } from '@/services/edition.service'

const props = defineProps<{ projectId: string }>()

const emit = defineEmits<{
  'select-document': [doc: EditionDocumentEntry]
}>()

const edition      = useEditionStore()
const loading      = ref(true)
const toggling     = ref<string | null>(null)
const selectedType = ref<string | null>(null)

const documents  = computed(() => edition.documents)
const liminaries = computed(() => documents.value.filter(d => d.category === 'liminary'))
const annexes    = computed(() => documents.value.filter(d => d.category === 'annex'))

onMounted(async () => {
  try {
    await edition.load(props.projectId)
  } finally {
    loading.value = false
  }
})

async function toggle(doc: EditionDocumentEntry) {
  toggling.value = doc.type
  const prev = doc.is_enabled
  doc.is_enabled = !doc.is_enabled
  try {
    const { data } = await editionService.toggleDocument(props.projectId, { type: doc.type, is_enabled: doc.is_enabled })
    // Mettre à jour l'id pour que EditionCenter puisse sauvegarder
    edition.setDocumentEnabled(doc.type, data.id ?? null, doc.is_enabled)
  } catch {
    doc.is_enabled = prev
    edition.setDocumentEnabled(doc.type, null, prev)
  } finally {
    toggling.value = null
  }
}

function select(doc: EditionDocumentEntry) {
  if (!doc.is_enabled) return
  selectedType.value = doc.type
  emit('select-document', doc)
}
</script>

<template>
  <div class="flex-1 overflow-y-auto py-2 px-2">
    <div v-if="loading" class="flex justify-center py-8">
      <svg class="w-4 h-4 animate-spin text-gray-400" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
      </svg>
    </div>

    <template v-else>
      <!-- Pages liminaires -->
      <div class="mb-3">
        <p class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wide px-1 mb-1">
          Pages liminaires
        </p>
        <div
          v-for="doc in liminaries"
          :key="doc.type"
          class="flex items-center gap-2 px-1 py-1.5 rounded-md group transition-colors"
          :class="[
            doc.is_enabled && selectedType === doc.type
              ? 'bg-brand-50 dark:bg-brand-900/20'
              : doc.is_enabled
                ? 'hover:bg-gray-100 dark:hover:bg-gray-800 cursor-pointer'
                : 'opacity-50'
          ]"
          @click="select(doc)"
        >
          <!-- Indicateur état -->
          <span
            class="shrink-0 w-2 h-2 rounded-full border transition-colors"
            :class="doc.is_enabled
              ? 'bg-brand-500 border-brand-500'
              : 'bg-transparent border-gray-400 dark:border-gray-500'"
          />
          <span
            class="flex-1 text-xs truncate"
            :class="doc.is_enabled
              ? 'text-gray-700 dark:text-gray-200'
              : 'text-gray-400 dark:text-gray-500'"
          >
            {{ doc.title || doc.label }}
          </span>
          <!-- Toggle -->
          <button
            class="shrink-0 p-0.5 rounded transition-all md:opacity-0 group-hover:opacity-100"
            :class="doc.is_enabled
              ? 'text-brand-500 hover:text-brand-700 dark:hover:text-brand-300'
              : 'text-gray-400 hover:text-gray-600 dark:hover:text-gray-300'"
            :disabled="toggling === doc.type"
            :title="doc.is_enabled ? 'Désactiver' : 'Activer'"
            @click.stop="toggle(doc)"
          >
            <svg v-if="doc.is_enabled" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            <svg v-else class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
          </button>
        </div>
      </div>

      <!-- Annexes -->
      <div>
        <p class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wide px-1 mb-1">
          Annexes
        </p>
        <div
          v-for="doc in annexes"
          :key="doc.type"
          class="flex items-center gap-2 px-1 py-1.5 rounded-md group transition-colors"
          :class="[
            doc.is_enabled && selectedType === doc.type
              ? 'bg-brand-50 dark:bg-brand-900/20'
              : doc.is_enabled
                ? 'hover:bg-gray-100 dark:hover:bg-gray-800 cursor-pointer'
                : 'opacity-50'
          ]"
          @click="select(doc)"
        >
          <span
            class="shrink-0 w-2 h-2 rounded-full border transition-colors"
            :class="doc.is_enabled
              ? 'bg-brand-500 border-brand-500'
              : 'bg-transparent border-gray-400 dark:border-gray-500'"
          />
          <span
            class="flex-1 text-xs truncate"
            :class="doc.is_enabled
              ? 'text-gray-700 dark:text-gray-200'
              : 'text-gray-400 dark:text-gray-500'"
          >
            {{ doc.title || doc.label }}
          </span>
          <button
            class="shrink-0 p-0.5 rounded transition-all md:opacity-0 group-hover:opacity-100"
            :class="doc.is_enabled
              ? 'text-brand-500 hover:text-brand-700 dark:hover:text-brand-300'
              : 'text-gray-400 hover:text-gray-600 dark:hover:text-gray-300'"
            :disabled="toggling === doc.type"
            :title="doc.is_enabled ? 'Désactiver' : 'Activer'"
            @click.stop="toggle(doc)"
          >
            <svg v-if="doc.is_enabled" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            <svg v-else class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
          </button>
        </div>
      </div>
    </template>
  </div>
</template>
