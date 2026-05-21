<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { adminService } from '@/services/admin.service'
import { projectsService } from '@/services/projects.service'
import type { ContentType, Workshop } from '@/types'

type ContentTypeItem = Pick<ContentType, 'id' | 'name' | 'short_name' | 'slug'>

const workshops    = ref<Workshop[]>([])
const contentTypes = ref<ContentTypeItem[]>([])
const loading      = ref(true)
const saving       = ref<number | null>(null)

onMounted(async () => {
  try {
    const [{ workshops: data }, ctRes] = await Promise.all([
      adminService.getWorkshops(),
      projectsService.getActiveContentTypes(),
    ])
    workshops.value = data
    contentTypes.value = ctRes.content_types
  } finally {
    loading.value = false
  }
})

async function toggle(w: Workshop, field: 'is_active' | 'is_premium') {
  saving.value = w.id
  const prev = w[field]
  w[field] = !w[field]
  try {
    const { workshop } = await adminService.updateWorkshop(w.id, { [field]: w[field] })
    Object.assign(w, workshop)
  } catch {
    w[field] = prev
  } finally {
    saving.value = null
  }
}

async function assignContentType(w: Workshop, contentTypeId: string | null) {
  saving.value = w.id
  const prev = w.content_type_id
  w.content_type_id = contentTypeId
  try {
    const { workshop } = await adminService.updateWorkshop(w.id, { content_type_id: contentTypeId })
    Object.assign(w, workshop)
  } catch {
    w.content_type_id = prev
  } finally {
    saving.value = null
  }
}

// Workshops groupés par type de contenu
const grouped = computed(() => {
  const groups: { ct: ContentTypeItem | null; workshops: Workshop[] }[] = []

  // Ateliers assignés à un type de contenu
  for (const ct of contentTypes.value) {
    const wks = workshops.value.filter(w => w.content_type_id === ct.id)
    if (wks.length > 0) groups.push({ ct, workshops: wks })
  }

  // Ateliers non assignés
  const unassigned = workshops.value.filter(w => !w.content_type_id)
  if (unassigned.length > 0) groups.push({ ct: null, workshops: unassigned })

  return groups
})
</script>

<template>
  <div class="p-8 max-w-4xl mx-auto">
    <div class="mb-6">
      <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-100">Ateliers</h1>
      <p class="text-sm text-gray-400 mt-1">Gérer la disponibilité des ateliers par type de projet.</p>
    </div>

    <div v-if="loading" class="text-gray-400 text-sm">Chargement…</div>

    <div v-else class="space-y-6">
      <div
        v-for="group in grouped"
        :key="group.ct?.id ?? '__unassigned__'"
        class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 overflow-hidden"
      >
        <!-- En-tête du groupe -->
        <div class="px-5 py-3 border-b border-gray-100 dark:border-gray-800 flex items-center gap-2">
          <span
            v-if="group.ct"
            class="text-sm font-semibold text-gray-900 dark:text-gray-100"
          >{{ group.ct.name }}</span>
          <span
            v-else
            class="text-sm font-semibold text-gray-400 dark:text-gray-500 italic"
          >Non assigné à un type de projet</span>
          <span
            v-if="group.ct?.slug"
            class="text-xs px-1.5 py-0.5 rounded bg-gray-100 dark:bg-gray-800 text-gray-400 font-mono"
          >{{ group.ct.slug }}</span>
        </div>

        <!-- Table des ateliers du groupe -->
        <table class="w-full text-sm">
          <thead>
            <tr class="border-b border-gray-50 dark:border-gray-800/80 text-left">
              <th class="th">Atelier</th>
              <th class="th">Description</th>
              <th class="th">Type de projet</th>
              <th class="th text-center">Actif</th>
              <th class="th text-center">Premium</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="w in group.workshops"
              :key="w.id"
              class="border-b border-gray-50 dark:border-gray-800/50 last:border-0"
            >
              <td class="td">
                <span class="font-medium text-gray-900 dark:text-gray-100">{{ w.label }}</span>
                <code class="ml-2 text-xs bg-gray-100 dark:bg-gray-800 px-1.5 py-0.5 rounded text-gray-500">{{ w.key }}</code>
              </td>
              <td class="td text-gray-500 max-w-xs truncate">{{ w.description || '—' }}</td>
              <td class="td">
                <select
                  class="text-xs rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800
                         text-gray-700 dark:text-gray-300 px-2 py-1 focus:outline-none focus:ring-1 focus:ring-brand-500"
                  :disabled="saving === w.id"
                  :value="w.content_type_id ?? ''"
                  @change="assignContentType(w, ($event.target as HTMLSelectElement).value || null)"
                >
                  <option value="">— Tous les types —</option>
                  <option
                    v-for="ct in contentTypes"
                    :key="ct.id"
                    :value="ct.id"
                  >{{ ct.short_name || ct.name }}</option>
                </select>
              </td>
              <td class="td text-center">
                <button
                  class="relative inline-flex h-5 w-9 rounded-full transition-colors"
                  :class="w.is_active ? 'bg-brand-600' : 'bg-gray-300 dark:bg-gray-700'"
                  :disabled="saving === w.id"
                  @click="toggle(w, 'is_active')"
                >
                  <span class="absolute top-0.5 left-0.5 h-4 w-4 rounded-full bg-white shadow transition-transform"
                    :class="w.is_active ? 'translate-x-4' : 'translate-x-0'" />
                </button>
              </td>
              <td class="td text-center">
                <button
                  class="relative inline-flex h-5 w-9 rounded-full transition-colors"
                  :class="w.is_premium ? 'bg-amber-500' : 'bg-gray-300 dark:bg-gray-700'"
                  :disabled="saving === w.id"
                  @click="toggle(w, 'is_premium')"
                >
                  <span class="absolute top-0.5 left-0.5 h-4 w-4 rounded-full bg-white shadow transition-transform"
                    :class="w.is_premium ? 'translate-x-4' : 'translate-x-0'" />
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Si aucun atelier du tout -->
      <p v-if="workshops.length === 0" class="text-sm text-gray-400">Aucun atelier configuré.</p>
    </div>
  </div>
</template>

<style scoped>
@reference "@/assets/main.css";
.th { @apply px-4 py-2.5 text-gray-500 dark:text-gray-500 font-medium text-xs; }
.td { @apply px-4 py-3; }
</style>
