<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'
import { adminService } from '@/services/admin.service'
import type { ContentType } from '@/types'

const contentTypes = ref<ContentType[]>([])
const loading = ref(true)

const showForm = ref(false)
const editTarget = ref<ContentType | null>(null)
const saving = ref(false)
const formError = ref('')

const form = reactive({
  name: '',
  slug: '',
  is_active: true,
  description: '',
  type_schema: '',
})

onMounted(fetchTypes)

async function fetchTypes() {
  loading.value = true
  try {
    const data = await adminService.getContentTypes()
    contentTypes.value = data.content_types
  } finally {
    loading.value = false
  }
}

function openCreate() {
  editTarget.value = null
  form.name = ''
  form.slug = ''
  form.is_active = true
  form.description = ''
  form.type_schema = '{}'
  formError.value = ''
  showForm.value = true
}

function openEdit(ct: ContentType) {
  editTarget.value = ct
  form.name = ct.name
  form.slug = ct.slug
  form.is_active = ct.is_active
  form.description = ct.description ?? ''
  form.type_schema = ct.type_schema ? JSON.stringify(ct.type_schema, null, 2) : '{}'
  formError.value = ''
  showForm.value = true
}

async function toggleActive(ct: ContentType) {
  ct.is_active = !ct.is_active
  try {
    await adminService.updateContentType(ct.id, { is_active: ct.is_active })
  } catch {
    ct.is_active = !ct.is_active
  }
}

function validateSchema() {
  try {
    JSON.parse(form.type_schema)
    return true
  } catch {
    formError.value = 'Le JSON du schéma est invalide.'
    return false
  }
}

async function save() {
  if (!validateSchema()) return
  formError.value = ''
  saving.value = true
  try {
    const payload = {
      name: form.name,
      slug: form.slug,
      is_active: form.is_active,
      description: form.description || null,
      type_schema: form.type_schema,
    }
    if (editTarget.value) {
      const { content_type } = await adminService.updateContentType(editTarget.value.id, payload)
      const idx = contentTypes.value.findIndex(c => c.id === editTarget.value!.id)
      if (idx !== -1) contentTypes.value[idx] = content_type
    } else {
      const { content_type } = await adminService.createContentType(payload)
      contentTypes.value.push(content_type)
    }
    showForm.value = false
  } catch (e: any) {
    formError.value = e?.response?.data?.message ?? 'Erreur lors de la sauvegarde.'
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <div class="p-8">
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-100">Types de contenu</h1>
      <button class="px-3 py-1.5 text-sm rounded-md bg-brand-600 text-white hover:bg-brand-700 transition-colors" @click="openCreate">
        + Nouveau type
      </button>
    </div>

    <div v-if="loading" class="text-gray-400 text-sm">Chargement…</div>

    <div v-else class="bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-800 overflow-hidden">
      <table class="w-full text-sm">
        <thead>
          <tr class="border-b border-gray-100 dark:border-gray-800 text-left">
            <th class="th">Nom</th>
            <th class="th">Slug</th>
            <th class="th text-right">Projets</th>
            <th class="th text-center">Actif</th>
            <th class="th">Description</th>
            <th class="th"></th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="ct in contentTypes"
            :key="ct.id"
            class="border-b border-gray-50 dark:border-gray-800/50 last:border-0"
          >
            <td class="td font-medium text-gray-900 dark:text-gray-100">{{ ct.name }}</td>
            <td class="td"><code class="text-xs bg-gray-100 dark:bg-gray-800 px-1.5 py-0.5 rounded">{{ ct.slug }}</code></td>
            <td class="td text-right text-gray-500">{{ ct.projects_count ?? 0 }}</td>
            <td class="td text-center">
              <button
                class="relative inline-flex h-5 w-9 rounded-full transition-colors"
                :class="ct.is_active ? 'bg-brand-600' : 'bg-gray-300 dark:bg-gray-700'"
                @click="toggleActive(ct)"
              >
                <span
                  class="absolute top-0.5 left-0.5 h-4 w-4 rounded-full bg-white shadow transition-transform"
                  :class="ct.is_active ? 'translate-x-4' : 'translate-x-0'"
                />
              </button>
            </td>
            <td class="td text-gray-500 max-w-xs truncate">{{ ct.description || '—' }}</td>
            <td class="td">
              <button class="text-xs text-brand-600 dark:text-brand-400 hover:underline" @click="openEdit(ct)">Éditer</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Form modal -->
    <Transition name="modal">
      <div v-if="showForm" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40" @click.self="showForm = false">
        <div class="bg-white dark:bg-gray-900 rounded-xl shadow-xl w-full max-w-2xl p-6 border border-gray-200 dark:border-gray-800 max-h-[90vh] overflow-y-auto">
          <h2 class="text-base font-semibold text-gray-900 dark:text-gray-100 mb-4">
            {{ editTarget ? 'Modifier le type' : 'Nouveau type de contenu' }}
          </h2>
          <form class="space-y-4" @submit.prevent="save">
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="admin-label">Nom</label>
                <input v-model="form.name" class="admin-input" type="text" required />
              </div>
              <div>
                <label class="admin-label">Slug</label>
                <input v-model="form.slug" class="admin-input" type="text" :disabled="!!editTarget" required pattern="[a-z0-9\-]+" />
                <p class="text-xs text-gray-400 mt-1">Lettres minuscules, chiffres, tirets.</p>
              </div>
            </div>

            <div class="flex items-center gap-3">
              <button
                type="button"
                class="relative inline-flex h-5 w-9 rounded-full transition-colors"
                :class="form.is_active ? 'bg-brand-600' : 'bg-gray-300 dark:bg-gray-700'"
                @click="form.is_active = !form.is_active"
              >
                <span
                  class="absolute top-0.5 left-0.5 h-4 w-4 rounded-full bg-white shadow transition-transform"
                  :class="form.is_active ? 'translate-x-4' : 'translate-x-0'"
                />
              </button>
              <span class="text-sm text-gray-700 dark:text-gray-300">Actif</span>
            </div>

            <div>
              <label class="admin-label">Description (Markdown)</label>
              <textarea v-model="form.description" class="admin-input font-mono" rows="4" placeholder="Description en Markdown…" />
            </div>

            <div>
              <label class="admin-label">Schéma JSON des paramètres</label>
              <textarea v-model="form.type_schema" class="admin-input font-mono text-xs" rows="6" placeholder="{}" spellcheck="false" />
              <p class="text-xs text-gray-400 mt-1">Structure JSON des paramètres spécifiques à ce type ({} par défaut).</p>
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
@reference "@/assets/main.css";
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
