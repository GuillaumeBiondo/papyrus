<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import type { Arc, Chapter, Todo } from '@/types'
import { arcsService } from '@/services/arcs.service'
import { chaptersService } from '@/services/chapters.service'
import { todosService } from '@/services/todos.service'
import { useAuthStore } from '@/stores/auth.store'
import { useAppConfigStore } from '@/stores/appConfig.store'
import PremiumLock from '@/components/common/PremiumLock.vue'
import VoiceInputText from '@/components/shared/VoiceInputText.vue'

const props = defineProps<{
  type: 'arc' | 'chapter'
  item: Arc | Chapter
}>()

const emit = defineEmits<{
  close: []
  'update:summary': [payload: { summary: string | null; generatedAt?: string | null }]
}>()

const auth     = useAuthStore()
const appStore = useAppConfigStore()

const tab = ref<'summary' | 'todos'>('summary')

// ── Summary ───────────────────────────────────────────────────

const summary            = ref(props.item.summary ?? '')
const summaryGeneratedAt = ref(props.item.summary_generated_at ?? null)
const summaryDirty       = ref(false)
const summarySaving      = ref(false)
const summaryGenning     = ref(false)
const summaryError       = ref<string | null>(null)

const generatedAtLabel = computed(() => {
  if (!summaryGeneratedAt.value) return null
  return new Intl.DateTimeFormat('fr-FR', { day: 'numeric', month: 'long', year: 'numeric' })
    .format(new Date(summaryGeneratedAt.value))
})

const summaryAutoIsPremium = computed(() => appStore.config?.summary_auto_is_premium ?? false)
const canGenerateSummary   = computed(() =>
  !summaryAutoIsPremium.value || (auth.user?.effective_premium ?? false)
)

watch(() => props.item.summary, (v) => {
  summary.value      = v ?? ''
  summaryDirty.value = false
})
watch(() => props.item.summary_generated_at, (v) => {
  summaryGeneratedAt.value = v ?? null
})

async function saveSummary() {
  if (!summaryDirty.value) return
  summarySaving.value = true
  try {
    const svc = props.type === 'arc' ? arcsService : chaptersService
    const res = await svc.saveSummary(props.item.id, summary.value || null)
    emit('update:summary', { summary: res.summary })
    summaryDirty.value = false
  } finally {
    summarySaving.value = false
  }
}

async function generateSummary() {
  summaryGenning.value = true
  summaryError.value   = null
  try {
    const svc = props.type === 'arc' ? arcsService : chaptersService
    const res = await svc.generateSummary(props.item.id)
    summary.value            = res.summary
    summaryGeneratedAt.value = res.summary_generated_at ?? null
    summaryDirty.value       = false
    emit('update:summary', { summary: res.summary, generatedAt: res.summary_generated_at ?? null })
  } catch (e: unknown) {
    const ax = e as { response?: { data?: { error?: string } } }
    summaryError.value = ax?.response?.data?.error ?? 'Erreur lors de la génération.'
  } finally {
    summaryGenning.value = false
  }
}

// ── Todos ─────────────────────────────────────────────────────

const todos        = ref<Todo[]>([])
const todosLoading = ref(false)
const newTodoText  = ref('')
const addingTodo   = ref(false)

async function loadTodos() {
  if (todosLoading.value) return
  todosLoading.value = true
  try {
    if (props.type === 'arc') {
      todos.value = await todosService.forArc(props.item.id)
    } else {
      todos.value = await todosService.forChapter(props.item.id)
    }
  } finally {
    todosLoading.value = false
  }
}

watch(tab, (t) => {
  if (t === 'todos' && todos.value.length === 0) loadTodos()
})

async function addTodo() {
  const text = newTodoText.value.trim()
  if (!text) return
  addingTodo.value = true
  try {
    let todo: Todo
    if (props.type === 'arc') {
      todo = await todosService.createForArc(props.item.id, text)
    } else {
      todo = await todosService.createForChapter(props.item.id, text)
    }
    todos.value.push(todo)
    newTodoText.value = ''
  } finally {
    addingTodo.value = false
  }
}

async function toggleTodo(todo: Todo) {
  const updated = await todosService.update(todo.id, { is_done: !todo.is_done })
  const idx = todos.value.findIndex(t => t.id === todo.id)
  if (idx !== -1) todos.value[idx] = updated
}

async function deleteTodo(todo: Todo) {
  await todosService.destroy(todo.id)
  todos.value = todos.value.filter(t => t.id !== todo.id)
}

const editingTodoId  = ref<string | null>(null)
const editingTodoText = ref('')

function startEditTodo(todo: Todo) {
  editingTodoId.value   = todo.id
  editingTodoText.value = todo.text
}

async function saveEditTodo(todo: Todo) {
  const text = editingTodoText.value.trim()
  if (!text) return
  const updated = await todosService.update(todo.id, { text })
  const idx = todos.value.findIndex(t => t.id === todo.id)
  if (idx !== -1) todos.value[idx] = updated
  editingTodoId.value = null
}

const doneTodos    = computed(() => todos.value.filter(t => t.is_done))
const pendingTodos = computed(() => todos.value.filter(t => !t.is_done))

const typeLabel = computed(() => props.type === 'arc' ? "l'arc" : 'le chapitre')
</script>

<template>
  <Teleport to="body">
    <div
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
      @click.self="emit('close')"
    >
      <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-2xl w-full max-w-2xl flex flex-col border border-gray-200 dark:border-gray-700 h-[80vh]">

        <!-- Header -->
        <div class="flex items-center gap-3 px-5 py-4 border-b border-gray-100 dark:border-gray-800 shrink-0">
          <div class="flex-1 min-w-0">
            <p class="text-[10px] font-medium uppercase tracking-widest text-gray-400 dark:text-gray-500">
              {{ type === 'arc' ? 'Arc' : 'Chapitre' }}
            </p>
            <h2 class="text-sm font-semibold text-gray-900 dark:text-gray-100 truncate mt-0.5">{{ item.title }}</h2>
          </div>
          <button
            class="shrink-0 p-1.5 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
            @click="emit('close')"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
          </button>
        </div>

        <!-- Tabs -->
        <div class="flex border-b border-gray-100 dark:border-gray-800 shrink-0">
          <button
            class="flex items-center gap-1.5 px-4 py-2.5 text-xs font-medium transition-colors border-b-2"
            :class="tab === 'summary'
              ? 'border-brand-500 text-brand-600 dark:text-brand-400'
              : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'"
            @click="tab = 'summary'"
          >
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            Résumé
          </button>
          <button
            class="flex items-center gap-1.5 px-4 py-2.5 text-xs font-medium transition-colors border-b-2"
            :class="tab === 'todos'
              ? 'border-brand-500 text-brand-600 dark:text-brand-400'
              : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'"
            @click="tab = 'todos'; if (todos.length === 0) loadTodos()"
          >
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
            </svg>
            Todos
            <span
              v-if="pendingTodos.length"
              class="ml-1 min-w-[1.1rem] h-4 rounded-full bg-brand-100 dark:bg-brand-900/40 text-brand-600 dark:text-brand-400 text-[10px] font-semibold flex items-center justify-center px-1"
            >{{ pendingTodos.length }}</span>
          </button>
        </div>

        <!-- Tab: Résumé -->
        <div v-if="tab === 'summary'" class="flex flex-col gap-3 p-5 flex-1 min-h-0">

          <!-- Bouton résumé automatique -->
          <div class="flex items-center gap-2 flex-wrap">
            <button
              class="flex items-center gap-2 px-3 py-1.5 rounded-lg text-xs font-medium transition-colors"
              :class="canGenerateSummary
                ? 'bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-400 hover:bg-amber-100 dark:hover:bg-amber-900/30 border border-amber-200 dark:border-amber-800'
                : 'bg-gray-100 dark:bg-gray-800 text-gray-400 cursor-not-allowed border border-gray-200 dark:border-gray-700'"
              :disabled="summaryGenning || !canGenerateSummary"
              :title="canGenerateSummary ? `Générer un résumé automatique de ${typeLabel}` : 'Fonctionnalité premium'"
              @click="canGenerateSummary && generateSummary()"
            >
              <svg
                v-if="summaryGenning"
                class="w-3.5 h-3.5 animate-spin"
                fill="none" viewBox="0 0 24 24"
              >
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
              </svg>
              <template v-else>
                <PremiumLock v-if="!canGenerateSummary" size="xs" />
                <span v-else class="sparkle-stars" aria-hidden="true">
                  <span class="star star-1">✦</span><span class="star star-2">✦</span><span class="star star-3">✦</span>
                </span>
              </template>
              {{ summaryGenning ? 'Génération…' : 'Résumé automatique' }}
              <span v-if="!summaryGenning && canGenerateSummary" class="sparkle-stars" aria-hidden="true">
                <span class="star star-3">✦</span><span class="star star-2">✦</span><span class="star star-1">✦</span>
              </span>
            </button>
            <span v-if="!canGenerateSummary" class="text-[11px] text-amber-500">Premium requis</span>
            <span v-if="generatedAtLabel && canGenerateSummary" class="text-[11px] text-gray-400 dark:text-gray-500 italic">
              Généré le {{ generatedAtLabel }}
            </span>
          </div>

          <p v-if="summaryError" class="text-xs text-red-500">{{ summaryError }}</p>

          <div class="relative flex-1 flex flex-col min-h-0">
            <textarea
              v-model="summary"
              placeholder="Écris un résumé de cet arc ou utilise le résumé automatique…"
              class="flex-1 w-full text-sm rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-gray-100 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-500 resize-none"
              @input="summaryDirty = true"
              @blur="saveSummary"
            />
            <span v-if="summarySaving" class="absolute bottom-2 right-2 text-[10px] text-gray-400">Enregistrement…</span>
          </div>
        </div>

        <!-- Tab: Todos -->
        <div v-else class="flex flex-col flex-1 overflow-hidden">
          <div v-if="todosLoading" class="flex-1 flex items-center justify-center">
            <svg class="w-5 h-5 animate-spin text-gray-400" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"/>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
            </svg>
          </div>

          <div v-else class="flex-1 overflow-y-auto px-4 pt-3 pb-2 space-y-1">
            <!-- Todos en cours -->
            <div v-if="pendingTodos.length === 0 && doneTodos.length === 0" class="text-center py-8 text-sm text-gray-400">
              Aucune todo pour {{ typeLabel }}.<br>
              <span class="text-xs">Ajoute-en une ci-dessous.</span>
            </div>

            <div
              v-for="todo in pendingTodos"
              :key="todo.id"
              class="group flex items-start gap-2 py-1.5"
            >
              <button
                class="shrink-0 mt-0.5 w-4 h-4 rounded border-2 border-gray-300 dark:border-gray-600 hover:border-brand-400 transition-colors flex items-center justify-center"
                @click="toggleTodo(todo)"
              />
              <template v-if="editingTodoId === todo.id">
                <input
                  v-model="editingTodoText"
                  class="flex-1 text-sm text-gray-900 dark:text-gray-100 bg-gray-50 dark:bg-gray-800 border border-brand-300 dark:border-brand-600 rounded px-2 py-0.5 focus:outline-none focus:ring-1 focus:ring-brand-500"
                  @keyup.enter="saveEditTodo(todo)"
                  @keyup.escape="editingTodoId = null"
                  @blur="saveEditTodo(todo)"
                />
              </template>
              <span
                v-else
                class="flex-1 text-sm text-gray-800 dark:text-gray-200 cursor-pointer"
                @dblclick="startEditTodo(todo)"
              >{{ todo.text }}</span>
              <div class="shrink-0 flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                <button class="p-0.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300" title="Modifier" @click="startEditTodo(todo)">
                  <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                  </svg>
                </button>
                <button class="p-0.5 text-gray-400 hover:text-red-500" title="Supprimer" @click="deleteTodo(todo)">
                  <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                  </svg>
                </button>
              </div>
            </div>

            <!-- Todos terminées -->
            <div v-if="doneTodos.length" class="pt-3 border-t border-gray-100 dark:border-gray-800 mt-2">
              <p class="text-[10px] font-medium uppercase tracking-widest text-gray-400 mb-1.5">Terminées</p>
              <div
                v-for="todo in doneTodos"
                :key="todo.id"
                class="group flex items-start gap-2 py-1"
              >
                <button
                  class="shrink-0 mt-0.5 w-4 h-4 rounded border-2 border-brand-400 bg-brand-400 flex items-center justify-center"
                  @click="toggleTodo(todo)"
                >
                  <svg class="w-2.5 h-2.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                  </svg>
                </button>
                <span class="flex-1 text-sm text-gray-400 dark:text-gray-500 line-through">{{ todo.text }}</span>
                <button class="shrink-0 p-0.5 opacity-0 group-hover:opacity-100 text-gray-400 hover:text-red-500 transition-opacity" @click="deleteTodo(todo)">
                  <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                  </svg>
                </button>
              </div>
            </div>
          </div>

          <!-- Saisie nouvelle todo -->
          <div class="px-4 py-3 border-t border-gray-100 dark:border-gray-800 shrink-0">
            <div class="flex gap-2">
              <VoiceInputText
                v-model="newTodoText"
                placeholder="Nouvelle todo…"
                source="todo"
                class="flex-1"
                @submit="addTodo"
              />
              <button
                class="px-3 py-1.5 rounded-lg bg-brand-600 hover:bg-brand-700 text-white text-xs font-medium transition-colors disabled:opacity-50"
                :disabled="!newTodoText.trim() || addingTodo"
                @click="addTodo"
              >Ajouter</button>
            </div>
          </div>
        </div>

      </div>
    </div>
  </Teleport>
</template>

<style scoped>
@keyframes star-twinkle {
  0%, 100% { opacity: 1; transform: scale(1); }
  50%       { opacity: 0.3; transform: scale(0.65); }
}

.sparkle-stars {
  display: inline-flex;
  align-items: center;
  gap: 1px;
  line-height: 1;
}

.star { display: inline-block; }
.star-1 { font-size: 11px; animation: star-twinkle 1.8s ease-in-out infinite 0s; }
.star-2 { font-size: 7px;  animation: star-twinkle 1.8s ease-in-out infinite 0.5s; }
.star-3 { font-size: 9px;  animation: star-twinkle 1.8s ease-in-out infinite 1s; }
</style>
