<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { adminService } from '@/services/admin.service'
import type { AdminStats, AdminUser } from '@/types'

const stats = ref<AdminStats | null>(null)
const recentUsers = ref<AdminUser[]>([])
const loading = ref(true)

onMounted(async () => {
  try {
    const data = await adminService.getDashboard()
    stats.value = data.stats
    recentUsers.value = data.recent_users
  } finally {
    loading.value = false
  }
})

function formatDate(iso: string | null) {
  if (!iso) return '—'
  return new Intl.DateTimeFormat('fr-FR', { dateStyle: 'medium' }).format(new Date(iso))
}

function formatNumber(n: number) {
  return new Intl.NumberFormat('fr-FR').format(n)
}
</script>

<template>
  <div class="p-8 max-w-5xl">
    <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-6">Tableau de bord</h1>

    <div v-if="loading" class="text-gray-400 text-sm">Chargement…</div>

    <template v-else-if="stats">
      <!-- Stats cards -->
      <div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-8">
        <div v-for="card in [
          { label: 'Utilisateurs', value: formatNumber(stats.total_users), sub: `+${stats.new_users_week} cette semaine` },
          { label: 'Actifs (7 jours)', value: formatNumber(stats.active_users_week) },
          { label: 'Projets', value: formatNumber(stats.total_projects) },
          { label: 'Mots écrits', value: formatNumber(stats.total_words) },
          { label: 'Administrateurs', value: formatNumber(stats.total_admins) },
        ]" :key="card.label" class="bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-800 p-4">
          <p class="text-xs text-gray-500 dark:text-gray-500 mb-1">{{ card.label }}</p>
          <p class="text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ card.value }}</p>
          <p v-if="card.sub" class="text-xs text-gray-400 mt-0.5">{{ card.sub }}</p>
        </div>
      </div>

      <!-- Recent users -->
      <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Inscriptions récentes</h2>
      <div class="bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-800 overflow-hidden">
        <table class="w-full text-sm">
          <thead>
            <tr class="border-b border-gray-100 dark:border-gray-800 text-left">
              <th class="px-4 py-2.5 text-gray-500 dark:text-gray-500 font-medium">Nom</th>
              <th class="px-4 py-2.5 text-gray-500 dark:text-gray-500 font-medium">Email</th>
              <th class="px-4 py-2.5 text-gray-500 dark:text-gray-500 font-medium">Inscription</th>
              <th class="px-4 py-2.5 text-gray-500 dark:text-gray-500 font-medium">Dernière connexion</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="u in recentUsers"
              :key="u.id"
              class="border-b border-gray-50 dark:border-gray-800/50 last:border-0"
            >
              <td class="px-4 py-2.5 text-gray-900 dark:text-gray-100">{{ u.name }}</td>
              <td class="px-4 py-2.5 text-gray-500">{{ u.email }}</td>
              <td class="px-4 py-2.5 text-gray-500">{{ formatDate(u.created_at) }}</td>
              <td class="px-4 py-2.5 text-gray-500">{{ formatDate(u.last_login_at) }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </template>
  </div>
</template>

