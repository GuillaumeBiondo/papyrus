<script setup lang="ts">
import { ref, watch } from 'vue'
import { projectsService } from '@/services/projects.service'
import type { Project } from '@/types'

const props = defineProps<{ project: Project }>()
const emit  = defineEmits<{
  close:   []
  updated: [project: Project]
  deleted: [id: string]
}>()

// ── Formulaire infos ──────────────────────────────────────────
const title  = ref(props.project.title)
const genre  = ref(props.project.genre ?? '')
const status = ref<Project['status']>(props.project.status)
const saving = ref(false)
const saved  = ref(false)

watch(() => props.project, p => {
  title.value  = p.title
  genre.value  = p.genre ?? ''
  status.value = p.status
})

const STATUS_OPTS: { value: Project['status']; label: string }[] = [
  { value: 'draft',       label: 'Brouillon' },
  { value: 'in_progress', label: 'En cours' },
  { value: 'revision',    label: 'Révision' },
  { value: 'complete',    label: 'Terminé' },
]

async function saveInfos() {
  saving.value = true
  saved.value  = false
  try {
    const updated = await projectsService.update(props.project.id, {
      title:  title.value.trim() || props.project.title,
      genre:  genre.value.trim() || null,
      status: status.value,
    })
    saved.value = true
    emit('updated', updated)
    setTimeout(() => { saved.value = false }, 2000)
  } finally {
    saving.value = false
  }
}

// ── Export ────────────────────────────────────────────────────
const exporting = ref<string | null>(null)

async function exportProject(format: 'txt' | 'md' | 'zip') {
  exporting.value = format
  try {
    const blob = await projectsService.export(props.project.id, format)
    const ext  = format
    const name = title.value.trim().replace(/\s+/g, '-').toLowerCase() + '.' + ext
    const url  = URL.createObjectURL(blob)
    const a    = document.createElement('a')
    a.href = url; a.download = name; a.click()
    URL.revokeObjectURL(url)
  } finally {
    exporting.value = null
  }
}

// ── Suppression ───────────────────────────────────────────────
const deleteStep    = ref<'idle' | 'confirm'>('idle')
const deleteConfirm = ref('')
const deleting      = ref(false)

async function confirmDelete() {
  if (deleteConfirm.value.trim().toLowerCase() !== props.project.title.trim().toLowerCase()) return
  deleting.value = true
  try {
    await projectsService.destroy(props.project.id)
    emit('deleted', props.project.id)
    emit('close')
  } finally {
    deleting.value = false
  }
}
</script>

<template>
  <Teleport to="body">
    <div
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm p-4"
      @click.self="emit('close')"
    >
      <div class="w-full max-w-lg bg-white dark:bg-gray-900 rounded-2xl shadow-2xl flex flex-col max-h-[90dvh] overflow-hidden">

        <!-- En-tête -->
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-800 shrink-0">
          <h2 class="font-semibold text-gray-900 dark:text-gray-100">Paramètres du roman</h2>
          <button class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 text-xl leading-none" @click="emit('close')">✕</button>
        </div>

        <div class="overflow-y-auto flex-1 px-6 py-5 space-y-8">

          <!-- ── Section : Informations ── -->
          <section>
            <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">Informations</h3>
            <div class="space-y-3">

              <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Titre</label>
                <input
                  v-model="title"
                  type="text"
                  class="w-full text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-500"
                />
              </div>

              <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Genre</label>
                <input
                  v-model="genre"
                  type="text"
                  placeholder="Fantasy, Thriller, Romance…"
                  class="w-full text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-500"
                />
              </div>

              <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Statut</label>
                <div class="flex gap-2 flex-wrap">
                  <button
                    v-for="opt in STATUS_OPTS"
                    :key="opt.value"
                    class="text-xs px-3 py-1.5 rounded-lg border transition-colors font-medium"
                    :class="status === opt.value
                      ? 'bg-brand-600 text-white border-brand-600'
                      : 'text-gray-500 dark:text-gray-400 border-gray-300 dark:border-gray-600 hover:border-brand-400'"
                    @click="status = opt.value"
                  >{{ opt.label }}</button>
                </div>
              </div>

              <div class="flex items-center gap-3 pt-1">
                <button
                  :disabled="saving"
                  class="px-4 py-2 text-sm font-medium rounded-lg bg-brand-600 text-white hover:bg-brand-700 disabled:opacity-50 transition-colors"
                  @click="saveInfos"
                >{{ saving ? 'Enregistrement…' : 'Enregistrer' }}</button>
                <Transition name="fade">
                  <span v-if="saved" class="text-xs text-green-600 dark:text-green-400">Modifications enregistrées ✓</span>
                </Transition>
              </div>
            </div>
          </section>

          <!-- ── Section : Export ── -->
          <section>
            <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">Exporter le roman</h3>
            <p class="text-xs text-gray-400 mb-3">Le roman est assemblé dans l'ordre arcs → chapitres → scènes.</p>
            <div class="grid grid-cols-2 gap-2">

              <button
                :disabled="exporting !== null"
                class="flex items-center gap-2 px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-700 hover:border-brand-400 hover:bg-brand-50 dark:hover:bg-brand-900/20 transition-colors text-sm text-gray-700 dark:text-gray-300 disabled:opacity-50"
                @click="exportProject('txt')"
              >
                <span class="text-lg">📄</span>
                <span>
                  <span class="font-medium block">Texte brut</span>
                  <span class="text-xs text-gray-400">.txt</span>
                </span>
                <span v-if="exporting === 'txt'" class="ml-auto text-xs text-gray-400">…</span>
              </button>

              <button
                :disabled="exporting !== null"
                class="flex items-center gap-2 px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-700 hover:border-brand-400 hover:bg-brand-50 dark:hover:bg-brand-900/20 transition-colors text-sm text-gray-700 dark:text-gray-300 disabled:opacity-50"
                @click="exportProject('md')"
              >
                <span class="text-lg">✍️</span>
                <span>
                  <span class="font-medium block">Markdown</span>
                  <span class="text-xs text-gray-400">.md</span>
                </span>
                <span v-if="exporting === 'md'" class="ml-auto text-xs text-gray-400">…</span>
              </button>

              <button
                disabled
                class="flex items-center gap-2 px-4 py-3 rounded-xl border border-gray-200 dark:border-gray-800 text-sm text-gray-400 opacity-50 cursor-not-allowed"
                title="Disponible prochainement"
              >
                <span class="text-lg">📕</span>
                <span>
                  <span class="font-medium block">PDF</span>
                  <span class="text-xs">Bientôt disponible</span>
                </span>
              </button>

              <button
                :disabled="exporting !== null"
                class="flex items-center gap-2 px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-700 hover:border-brand-400 hover:bg-brand-50 dark:hover:bg-brand-900/20 transition-colors text-sm text-gray-700 dark:text-gray-300 disabled:opacity-50"
                @click="exportProject('zip')"
              >
                <span class="text-lg">🗜️</span>
                <span>
                  <span class="font-medium block">ZIP</span>
                  <span class="text-xs text-gray-400">un fichier / scène</span>
                </span>
                <span v-if="exporting === 'zip'" class="ml-auto text-xs text-gray-400">…</span>
              </button>

            </div>
          </section>

          <!-- ── Section : Danger ── -->
          <section>
            <h3 class="text-xs font-semibold text-red-400 uppercase tracking-wider mb-3">Zone de danger</h3>

            <div v-if="deleteStep === 'idle'" class="flex items-center justify-between rounded-xl border border-red-200 dark:border-red-900 bg-red-50 dark:bg-red-900/10 px-4 py-3">
              <div>
                <p class="text-sm font-medium text-red-700 dark:text-red-400">Supprimer ce roman</p>
                <p class="text-xs text-red-500 mt-0.5">Toutes les scènes, annotations et fiches seront perdues définitivement.</p>
              </div>
              <button
                class="ml-4 shrink-0 px-3 py-1.5 text-xs font-medium rounded-lg bg-red-600 text-white hover:bg-red-700 transition-colors"
                @click="deleteStep = 'confirm'; deleteConfirm = ''"
              >Supprimer</button>
            </div>

            <div v-else class="rounded-xl border border-red-300 dark:border-red-800 bg-red-50 dark:bg-red-900/10 px-4 py-4 space-y-3">
              <p class="text-sm text-red-700 dark:text-red-400 font-medium">
                ⚠ Cette action est irréversible. Tous les arcs, chapitres, scènes, annotations, notes et fiches associés seront supprimés.
              </p>
              <p class="text-xs text-red-600 dark:text-red-400">
                Tape <strong>{{ project.title }}</strong> pour confirmer :
              </p>
              <input
                v-model="deleteConfirm"
                type="text"
                :placeholder="project.title"
                class="w-full text-sm rounded-lg border border-red-300 dark:border-red-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-500"
              />
              <div class="flex gap-2">
                <button
                  :disabled="deleteConfirm.trim().toLowerCase() !== project.title.trim().toLowerCase() || deleting"
                  class="px-4 py-2 text-sm font-medium rounded-lg bg-red-600 text-white hover:bg-red-700 disabled:opacity-40 transition-colors"
                  @click="confirmDelete"
                >{{ deleting ? 'Suppression…' : 'Confirmer la suppression' }}</button>
                <button
                  class="px-4 py-2 text-sm text-gray-500 hover:text-gray-700 dark:hover:text-gray-300"
                  @click="deleteStep = 'idle'"
                >Annuler</button>
              </div>
            </div>
          </section>

        </div>
      </div>
    </div>
  </Teleport>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.3s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
