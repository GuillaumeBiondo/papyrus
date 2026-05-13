<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { adminService } from '@/services/admin.service'
import type { Workshop } from '@/types'

const workshops = ref<Workshop[]>([])
const loading   = ref(true)
const saving    = ref<number | null>(null)

onMounted(async () => {
  try {
    const { workshops: data } = await adminService.getWorkshops()
    workshops.value = data
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
</script>

<template>
  <div class="p-8">
    <div class="mb-6">
      <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-100">Ateliers</h1>
      <p class="text-sm text-gray-400 mt-1">Gérer la disponibilité et l'accès premium des ateliers.</p>
    </div>

    <div v-if="loading" class="text-gray-400 text-sm">Chargement…</div>

    <div v-else class="bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-800 overflow-hidden">
      <table class="w-full text-sm">
        <thead>
          <tr class="border-b border-gray-100 dark:border-gray-800 text-left">
            <th class="th">Atelier</th>
            <th class="th">Description</th>
            <th class="th text-center">Actif</th>
            <th class="th text-center">Premium</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="w in workshops" :key="w.id" class="border-b border-gray-50 dark:border-gray-800/50 last:border-0">
            <td class="td">
              <span class="font-medium text-gray-900 dark:text-gray-100">{{ w.label }}</span>
              <code class="ml-2 text-xs bg-gray-100 dark:bg-gray-800 px-1.5 py-0.5 rounded text-gray-500">{{ w.key }}</code>
            </td>
            <td class="td text-gray-500 max-w-xs truncate">{{ w.description || '—' }}</td>
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
  </div>
</template>

<style scoped>
@reference "@/assets/main.css";
.th { @apply px-4 py-2.5 text-gray-500 dark:text-gray-500 font-medium text-xs; }
.td { @apply px-4 py-3; }
</style>
