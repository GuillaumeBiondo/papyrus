<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { adminService } from '@/services/admin.service'
import type { AdminUser } from '@/types'

const users = ref<AdminUser[]>([])
const loading = ref(true)
const error = ref('')

// Create user
const showCreate = ref(false)
const form = ref({ name: '', email: '', password: '' })
const creating = ref(false)
const createError = ref('')

// Preferences detail
const expanded = ref<string | null>(null)

onMounted(fetchUsers)

async function fetchUsers() {
  loading.value = true
  try {
    const data = await adminService.getUsers()
    users.value = data.users
  } catch {
    error.value = 'Impossible de charger les utilisateurs.'
  } finally {
    loading.value = false
  }
}

async function createUser() {
  createError.value = ''
  creating.value = true
  try {
    const { user } = await adminService.createUser(form.value)
    users.value.unshift(user as unknown as AdminUser)
    showCreate.value = false
    form.value = { name: '', email: '', password: '' }
  } catch (e: any) {
    createError.value = e?.response?.data?.message ?? 'Erreur lors de la création.'
  } finally {
    creating.value = false
  }
}

function formatDate(iso: string | null) {
  if (!iso) return '—'
  return new Intl.DateTimeFormat('fr-FR', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(iso))
}

function formatNumber(n: number) {
  return new Intl.NumberFormat('fr-FR').format(n)
}

function hasPreferences(u: AdminUser) {
  return u.preferences && Object.keys(u.preferences).length > 0
}
</script>

<template>
  <div class="p-8">
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-100">Utilisateurs</h1>
      <button
        class="px-3 py-1.5 text-sm rounded-md bg-brand-600 text-white hover:bg-brand-700 transition-colors"
        @click="showCreate = true"
      >
        + Nouveau compte
      </button>
    </div>

    <div v-if="loading" class="text-gray-400 text-sm">Chargement…</div>
    <div v-else-if="error" class="text-red-500 text-sm">{{ error }}</div>

    <template v-else>
      <div class="bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-800 overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="border-b border-gray-100 dark:border-gray-800 text-left">
              <th class="th">Nom</th>
              <th class="th">Email</th>
              <th class="th">Rôle</th>
              <th class="th">Dernière connexion</th>
              <th class="th text-right">Projets</th>
              <th class="th text-right">Arcs</th>
              <th class="th text-right">Chapitres</th>
              <th class="th text-right">Scènes</th>
              <th class="th text-right">Mots</th>
              <th class="th text-right">Moy./projet</th>
              <th class="th">Prefs</th>
            </tr>
          </thead>
          <tbody>
            <template v-for="u in users" :key="u.id">
              <tr class="border-b border-gray-50 dark:border-gray-800/50 hover:bg-gray-50 dark:hover:bg-gray-800/40">
                <td class="td font-medium text-gray-900 dark:text-gray-100">{{ u.name }}</td>
                <td class="td text-gray-500">{{ u.email }}</td>
                <td class="td">
                  <span class="inline-block px-2 py-0.5 rounded text-xs font-medium"
                        :class="u.role === 'admin'
                          ? 'bg-purple-100 text-purple-700 dark:bg-purple-950 dark:text-purple-300'
                          : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400'">
                    {{ u.role }}
                  </span>
                </td>
                <td class="td text-gray-500 whitespace-nowrap">{{ formatDate(u.last_login_at) }}</td>
                <td class="td text-right text-gray-700 dark:text-gray-300">{{ u.projects_count }}</td>
                <td class="td text-right text-gray-500">{{ u.arcs_count }}</td>
                <td class="td text-right text-gray-500">{{ u.chapters_count }}</td>
                <td class="td text-right text-gray-500">{{ u.scenes_count }}</td>
                <td class="td text-right text-gray-700 dark:text-gray-300">{{ formatNumber(u.total_words) }}</td>
                <td class="td text-right text-gray-500">{{ formatNumber(u.avg_words_per_project) }}</td>
                <td class="td">
                  <button
                    v-if="hasPreferences(u)"
                    class="text-xs text-brand-600 dark:text-brand-400 hover:underline"
                    @click="expanded = expanded === u.id ? null : u.id"
                  >
                    {{ expanded === u.id ? 'masquer' : 'voir' }}
                  </button>
                  <span v-else class="text-gray-300 dark:text-gray-700 text-xs">—</span>
                </td>
              </tr>
              <tr v-if="expanded === u.id" :key="`${u.id}-prefs`">
                <td colspan="11" class="px-4 py-3 bg-gray-50 dark:bg-gray-800/30">
                  <pre class="text-xs text-gray-600 dark:text-gray-400 whitespace-pre-wrap font-mono">{{ JSON.stringify(u.preferences, null, 2) }}</pre>
                </td>
              </tr>
            </template>
          </tbody>
        </table>
      </div>
    </template>

    <!-- Create modal -->
    <Transition name="modal">
      <div v-if="showCreate" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40" @click.self="showCreate = false">
        <div class="bg-white dark:bg-gray-900 rounded-xl shadow-xl w-full max-w-md p-6 border border-gray-200 dark:border-gray-800">
          <h2 class="text-base font-semibold text-gray-900 dark:text-gray-100 mb-4">Créer un compte utilisateur</h2>
          <form class="space-y-3" @submit.prevent="createUser">
            <div>
              <label class="admin-label">Nom</label>
              <input v-model="form.name" class="admin-input" type="text" required />
            </div>
            <div>
              <label class="admin-label">Email</label>
              <input v-model="form.email" class="admin-input" type="email" required />
            </div>
            <div>
              <label class="admin-label">Mot de passe</label>
              <input v-model="form.password" class="admin-input" type="password" required minlength="8" />
            </div>
            <p v-if="createError" class="text-red-500 text-xs">{{ createError }}</p>
            <div class="flex justify-end gap-2 pt-2">
              <button type="button" class="btn-ghost" @click="showCreate = false">Annuler</button>
              <button type="submit" :disabled="creating" class="btn-primary">
                {{ creating ? 'Création…' : 'Créer' }}
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
.th { @apply px-3 py-2.5 text-gray-500 dark:text-gray-500 font-medium text-xs; }
.td { @apply px-3 py-2.5; }
.admin-label { @apply block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1; }
.admin-input {
  @apply w-full rounded-md border border-gray-300 dark:border-gray-700
         bg-white dark:bg-gray-800 px-3 py-1.5 text-sm
         text-gray-900 dark:text-gray-100 outline-none
         focus:ring-2 focus:ring-brand-500 focus:border-brand-500;
}
.btn-ghost {
  @apply px-3 py-1.5 text-sm rounded-md text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800;
}
.btn-primary {
  @apply px-3 py-1.5 text-sm rounded-md bg-brand-600 text-white hover:bg-brand-700 disabled:opacity-50 transition-colors;
}
.modal-enter-active, .modal-leave-active { transition: opacity 0.15s; }
.modal-enter-from, .modal-leave-to { opacity: 0; }
</style>
