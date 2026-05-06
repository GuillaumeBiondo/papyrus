<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'
import { adminService } from '@/services/admin.service'
import type { Changelog } from '@/types'

const changelogs = ref<Changelog[]>([])
const loading = ref(true)
const deleting = ref<string | null>(null)

const showForm = ref(false)
const editTarget = ref<Changelog | null>(null)
const saving = ref(false)
const formError = ref('')
const preview = ref(false)

const form = reactive({
  version: '',
  title: '',
  body: '',
  published_at: '',
})

onMounted(fetchChangelogs)

async function fetchChangelogs() {
  loading.value = true
  try {
    const data = await adminService.getChangelogs()
    changelogs.value = data.changelogs
  } finally {
    loading.value = false
  }
}

function openCreate() {
  editTarget.value = null
  form.version = ''
  form.title = ''
  form.body = ''
  form.published_at = ''
  formError.value = ''
  preview.value = false
  showForm.value = true
}

function openEdit(cl: Changelog) {
  editTarget.value = cl
  form.version = cl.version ?? ''
  form.title = cl.title
  form.body = cl.body
  form.published_at = cl.published_at ? cl.published_at.slice(0, 16) : ''
  formError.value = ''
  preview.value = false
  showForm.value = true
}

async function save() {
  formError.value = ''
  saving.value = true
  try {
    const payload = {
      version: form.version || undefined,
      title: form.title,
      body: form.body,
      published_at: form.published_at || null,
    }
    if (editTarget.value) {
      const { changelog } = await adminService.updateChangelog(editTarget.value.id, payload)
      const idx = changelogs.value.findIndex(c => c.id === editTarget.value!.id)
      if (idx !== -1) changelogs.value[idx] = changelog
    } else {
      const { changelog } = await adminService.createChangelog(payload)
      changelogs.value.unshift(changelog)
    }
    showForm.value = false
  } catch (e: any) {
    formError.value = e?.response?.data?.message ?? 'Erreur lors de la sauvegarde.'
  } finally {
    saving.value = false
  }
}

async function remove(cl: Changelog) {
  if (!confirm(`Supprimer "${cl.title}" ?`)) return
  deleting.value = cl.id
  try {
    await adminService.deleteChangelog(cl.id)
    changelogs.value = changelogs.value.filter(c => c.id !== cl.id)
  } finally {
    deleting.value = null
  }
}

function publishLabel(cl: Changelog) {
  if (!cl.published_at) return 'Brouillon'
  const d = new Date(cl.published_at)
  if (d > new Date()) return 'Planifié'
  return 'Publié'
}

function formatDate(iso: string | null) {
  if (!iso) return '—'
  return new Intl.DateTimeFormat('fr-FR', { dateStyle: 'medium' }).format(new Date(iso))
}
</script>

<template>
  <div class="p-8">
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-100">Journaux de bord</h1>
      <button class="px-3 py-1.5 text-sm rounded-md bg-brand-600 text-white hover:bg-brand-700 transition-colors" @click="openCreate">
        + Nouveau
      </button>
    </div>

    <div v-if="loading" class="text-gray-400 text-sm">Chargement…</div>

    <div v-else class="bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-800 overflow-hidden">
      <table class="w-full text-sm">
        <thead>
          <tr class="border-b border-gray-100 dark:border-gray-800 text-left">
            <th class="th">Version</th>
            <th class="th">Titre</th>
            <th class="th">Statut</th>
            <th class="th">Date pub.</th>
            <th class="th text-right">Vus par</th>
            <th class="th"></th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="cl in changelogs"
            :key="cl.id"
            class="border-b border-gray-50 dark:border-gray-800/50 last:border-0"
          >
            <td class="td">
              <code v-if="cl.version" class="text-xs bg-gray-100 dark:bg-gray-800 px-1.5 py-0.5 rounded">{{ cl.version }}</code>
              <span v-else class="text-gray-400">—</span>
            </td>
            <td class="td font-medium text-gray-900 dark:text-gray-100">{{ cl.title }}</td>
            <td class="td">
              <span class="inline-block px-2 py-0.5 rounded text-xs font-medium"
                    :class="{
                      'bg-green-100 text-green-700 dark:bg-green-950 dark:text-green-300': publishLabel(cl) === 'Publié',
                      'bg-yellow-100 text-yellow-700 dark:bg-yellow-950 dark:text-yellow-300': publishLabel(cl) === 'Planifié',
                      'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400': publishLabel(cl) === 'Brouillon',
                    }">
                {{ publishLabel(cl) }}
              </span>
            </td>
            <td class="td text-gray-500">{{ formatDate(cl.published_at) }}</td>
            <td class="td text-right text-gray-500">{{ cl.reads_count ?? 0 }}</td>
            <td class="td flex items-center gap-3 justify-end">
              <button class="text-xs text-brand-600 dark:text-brand-400 hover:underline" @click="openEdit(cl)">Éditer</button>
              <button class="text-xs text-red-500 hover:underline" :disabled="deleting === cl.id" @click="remove(cl)">
                {{ deleting === cl.id ? '…' : 'Supprimer' }}
              </button>
            </td>
          </tr>
          <tr v-if="changelogs.length === 0">
            <td colspan="6" class="td text-center text-gray-400 py-8">Aucun journal.</td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Form modal -->
    <Transition name="modal">
      <div v-if="showForm" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40" @click.self="showForm = false">
        <div class="bg-white dark:bg-gray-900 rounded-xl shadow-xl w-full max-w-3xl p-6 border border-gray-200 dark:border-gray-800 max-h-[90vh] overflow-y-auto">
          <div class="flex items-center justify-between mb-4">
            <h2 class="text-base font-semibold text-gray-900 dark:text-gray-100">
              {{ editTarget ? 'Modifier' : 'Nouveau journal' }}
            </h2>
            <button
              type="button"
              class="text-xs px-2 py-1 rounded border text-gray-500 border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800"
              @click="preview = !preview"
            >
              {{ preview ? 'Éditer' : 'Aperçu' }}
            </button>
          </div>

          <form class="space-y-4" @submit.prevent="save">
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="admin-label">Version</label>
                <input v-model="form.version" class="admin-input" type="text" placeholder="ex. 1.2.0" />
              </div>
              <div>
                <label class="admin-label">Date de publication</label>
                <input v-model="form.published_at" class="admin-input" type="datetime-local" />
                <p class="text-xs text-gray-400 mt-1">Vide = brouillon.</p>
              </div>
            </div>

            <div>
              <label class="admin-label">Titre</label>
              <input v-model="form.title" class="admin-input" type="text" required />
            </div>

            <div>
              <label class="admin-label">Contenu (Markdown)</label>
              <div v-if="preview" class="admin-input min-h-40 prose prose-sm dark:prose-invert max-w-none whitespace-pre-wrap text-sm">
                {{ form.body || '(vide)' }}
              </div>
              <textarea
                v-else
                v-model="form.body"
                class="admin-input font-mono text-sm"
                rows="12"
                required
                placeholder="## Nouveautés&#10;&#10;- Feature 1&#10;- Feature 2"
              />
            </div>

            <p v-if="formError" class="text-red-500 text-xs">{{ formError }}</p>

            <div class="flex justify-end gap-2 pt-1">
              <button type="button" class="btn-ghost" @click="showForm = false">Annuler</button>
              <button type="submit" :disabled="saving" class="btn-primary">
                {{ saving ? 'Sauvegarde…' : 'Sauvegarder' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </Transition>
  </div>
</template>

<style scoped>
.th { @apply px-4 py-2.5 text-gray-500 dark:text-gray-500 font-medium text-xs; }
.td { @apply px-4 py-3; }
.admin-label { @apply block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1; }
.admin-input {
  @apply w-full rounded-md border border-gray-300 dark:border-gray-700
         bg-white dark:bg-gray-800 px-3 py-1.5 text-sm
         text-gray-900 dark:text-gray-100 outline-none
         focus:ring-2 focus:ring-brand-500 focus:border-brand-500;
}
.btn-ghost { @apply px-3 py-1.5 text-sm rounded-md text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800; }
.btn-primary { @apply px-3 py-1.5 text-sm rounded-md bg-brand-600 text-white hover:bg-brand-700 disabled:opacity-50 transition-colors; }
.modal-enter-active, .modal-leave-active { transition: opacity 0.15s; }
.modal-enter-from, .modal-leave-to { opacity: 0; }
</style>
