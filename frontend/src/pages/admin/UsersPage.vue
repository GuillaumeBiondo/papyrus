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

// Premium override
const togglingPremium = ref<string | null>(null)

async function togglePremiumOverride(u: AdminUser) {
  togglingPremium.value = u.id
  try {
    const { premium_override, effective_premium } = await adminService.updatePremiumOverride(u.id, !u.premium_override)
    const idx = users.value.findIndex(x => x.id === u.id)
    if (idx !== -1) users.value[idx] = { ...users.value[idx]!, premium_override, effective_premium }
  } finally {
    togglingPremium.value = null
  }
}

// Maintenance bypass
const togglingBypass = ref<string | null>(null)

async function toggleBypass(u: AdminUser) {
  togglingBypass.value = u.id
  try {
    const { maintenance_bypass } = await adminService.updateMaintenanceBypass(u.id, !u.maintenance_bypass)
    const idx = users.value.findIndex(x => x.id === u.id)
    if (idx !== -1) users.value[idx] = { ...users.value[idx]!, maintenance_bypass }
  } finally {
    togglingBypass.value = null
  }
}

// Block user
const togglingBlock = ref<string | null>(null)
const blockReasonInput = ref<string>('')
const showBlockReason = ref<string | null>(null)

async function toggleBlock(u: AdminUser) {
  if (!u.is_blocked && showBlockReason.value !== u.id) {
    showBlockReason.value = u.id
    blockReasonInput.value = ''
    return
  }
  togglingBlock.value = u.id
  try {
    const { is_blocked, block_reason } = await adminService.updateBlockedStatus(
      u.id,
      !u.is_blocked,
      !u.is_blocked ? blockReasonInput.value || null : null,
    )
    const idx = users.value.findIndex(x => x.id === u.id)
    if (idx !== -1) users.value[idx] = { ...users.value[idx]!, is_blocked, block_reason }
  } finally {
    togglingBlock.value = null
    showBlockReason.value = null
    blockReasonInput.value = ''
  }
}

function cancelBlock() {
  showBlockReason.value = null
  blockReasonInput.value = ''
}

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
  <div class="p-4 md:p-8">
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
      <!-- Mobile: cards -->
      <div class="md:hidden space-y-3">
        <template v-for="u in users" :key="u.id">
          <div class="bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-800 p-4">
            <!-- Header -->
            <div class="flex items-start justify-between gap-2 mb-1">
              <div class="flex items-center gap-2 flex-wrap">
                <span class="font-medium text-gray-900 dark:text-gray-100 text-sm">{{ u.name }}</span>
                <span
                  class="inline-block px-2 py-0.5 rounded text-xs font-medium"
                  :class="u.role === 'admin'
                    ? 'bg-purple-100 text-purple-700 dark:bg-purple-950 dark:text-purple-300'
                    : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400'"
                >
                  {{ u.role }}
                </span>
                <span
                  v-if="u.is_premium"
                  class="text-[10px] font-semibold px-1.5 py-0.5 rounded bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-400"
                >
                  ★ payé
                </span>
              </div>
              <button
                v-if="hasPreferences(u)"
                class="text-xs text-brand-600 dark:text-brand-400 hover:underline shrink-0"
                @click="expanded = expanded === u.id ? null : u.id"
              >
                {{ expanded === u.id ? 'masquer' : 'prefs' }}
              </button>
            </div>

            <!-- Email -->
            <p class="text-xs text-gray-500 truncate mb-2">{{ u.email }}</p>

            <!-- Stats -->
            <div class="flex flex-wrap gap-x-4 gap-y-0.5 text-xs text-gray-400 mb-3">
              <span>{{ u.projects_count }} projet(s)</span>
              <span>{{ formatNumber(u.total_words) }} mots</span>
              <span>Connexion : {{ formatDate(u.last_login_at) }}</span>
            </div>

            <!-- Toggles (non-admin only) -->
            <div v-if="u.role !== 'admin'" class="flex items-center gap-5">
              <div class="flex items-center gap-1.5">
                <span class="text-xs text-gray-500">Premium</span>
                <button
                  class="relative inline-flex h-5 w-9 rounded-full transition-colors"
                  :class="[
                    u.premium_override ? 'bg-amber-500' : 'bg-gray-300 dark:bg-gray-700',
                    togglingPremium === u.id ? 'opacity-50 pointer-events-none' : '',
                  ]"
                  :title="u.premium_override ? 'Premium forcé par admin' : 'Forcer le premium'"
                  @click="togglePremiumOverride(u)"
                >
                  <span class="absolute top-0.5 left-0.5 h-4 w-4 rounded-full bg-white shadow transition-transform"
                        :class="u.premium_override ? 'translate-x-4' : 'translate-x-0'" />
                </button>
              </div>
              <div class="flex items-center gap-1.5">
                <span class="text-xs text-gray-500">Maint.</span>
                <button
                  class="relative inline-flex h-5 w-9 rounded-full transition-colors"
                  :class="[
                    u.maintenance_bypass ? 'bg-brand-600' : 'bg-gray-300 dark:bg-gray-700',
                    togglingBypass === u.id ? 'opacity-50 pointer-events-none' : '',
                  ]"
                  :title="u.maintenance_bypass ? 'Accès autorisé pendant la maintenance' : 'Bloqué pendant la maintenance'"
                  @click="toggleBypass(u)"
                >
                  <span class="absolute top-0.5 left-0.5 h-4 w-4 rounded-full bg-white shadow transition-transform"
                        :class="u.maintenance_bypass ? 'translate-x-4' : 'translate-x-0'" />
                </button>
              </div>
              <div class="flex items-center gap-1.5">
                <span class="text-xs text-gray-500">Bloqué</span>
                <button
                  class="relative inline-flex h-5 w-9 rounded-full transition-colors"
                  :class="[
                    u.is_blocked ? 'bg-red-500' : 'bg-gray-300 dark:bg-gray-700',
                    togglingBlock === u.id ? 'opacity-50 pointer-events-none' : '',
                  ]"
                  :title="u.is_blocked ? `Bloqué${u.block_reason ? ' : ' + u.block_reason : ''}` : 'Compte actif'"
                  @click="toggleBlock(u)"
                >
                  <span class="absolute top-0.5 left-0.5 h-4 w-4 rounded-full bg-white shadow transition-transform"
                        :class="u.is_blocked ? 'translate-x-4' : 'translate-x-0'" />
                </button>
              </div>
            </div>

            <!-- Block reason input -->
            <div v-if="showBlockReason === u.id" class="mt-3 flex items-center gap-2">
              <input
                v-model="blockReasonInput"
                type="text"
                maxlength="255"
                placeholder="Raison (optionnelle)…"
                class="flex-1 rounded border border-red-200 dark:border-red-800 bg-white dark:bg-gray-900 px-2 py-1 text-xs text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-1 focus:ring-red-400"
              />
              <button
                class="px-2.5 py-1 text-xs rounded bg-red-600 text-white hover:bg-red-700 transition-colors"
                :disabled="togglingBlock === u.id"
                @click="toggleBlock(u)"
              >
                OK
              </button>
              <button class="px-2.5 py-1 text-xs text-gray-500" @click="cancelBlock">✕</button>
            </div>

            <!-- Preferences expanded -->
            <div v-if="expanded === u.id" class="mt-3 bg-gray-50 dark:bg-gray-800/30 rounded p-2">
              <pre class="text-xs text-gray-600 dark:text-gray-400 whitespace-pre-wrap font-mono overflow-x-auto">{{ JSON.stringify(u.preferences, null, 2) }}</pre>
            </div>
          </div>
        </template>
        <p v-if="users.length === 0" class="text-center text-gray-400 text-sm py-8">Aucun utilisateur.</p>
      </div>

      <!-- Desktop: table -->
      <div class="hidden md:block bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-800 overflow-x-auto">
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
              <th class="th text-center">Premium</th>
              <th class="th">Maint.</th>
              <th class="th">Bloqué</th>
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
                <td class="td text-center">
                  <div class="flex items-center justify-center gap-1.5">
                    <span
                      v-if="u.is_premium"
                      class="text-[10px] font-semibold px-1.5 py-0.5 rounded bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-400"
                      title="Abonnement actif"
                    >★ payé</span>
                    <button
                      v-if="u.role !== 'admin'"
                      class="relative inline-flex h-5 w-9 rounded-full transition-colors"
                      :class="[
                        u.premium_override ? 'bg-amber-500' : 'bg-gray-300 dark:bg-gray-700',
                        togglingPremium === u.id ? 'opacity-50 pointer-events-none' : '',
                      ]"
                      :title="u.premium_override ? 'Premium forcé par admin' : 'Forcer le premium'"
                      @click="togglePremiumOverride(u)"
                    >
                      <span class="absolute top-0.5 left-0.5 h-4 w-4 rounded-full bg-white shadow transition-transform"
                        :class="u.premium_override ? 'translate-x-4' : 'translate-x-0'" />
                    </button>
                    <span v-else class="text-xs text-gray-300 dark:text-gray-700">—</span>
                  </div>
                </td>
                <td class="td">
                  <button
                    v-if="u.role !== 'admin'"
                    class="relative inline-flex h-5 w-9 rounded-full transition-colors"
                    :class="[
                      u.maintenance_bypass ? 'bg-brand-600' : 'bg-gray-300 dark:bg-gray-700',
                      togglingBypass === u.id ? 'opacity-50 pointer-events-none' : '',
                    ]"
                    :title="u.maintenance_bypass ? 'Accès autorisé pendant la maintenance' : 'Bloqué pendant la maintenance'"
                    @click="toggleBypass(u)"
                  >
                    <span
                      class="absolute top-0.5 left-0.5 h-4 w-4 rounded-full bg-white shadow transition-transform"
                      :class="u.maintenance_bypass ? 'translate-x-4' : 'translate-x-0'"
                    />
                  </button>
                  <span v-else class="text-xs text-gray-300 dark:text-gray-700">—</span>
                </td>
                <td class="td">
                  <button
                    v-if="u.role !== 'admin'"
                    class="relative inline-flex h-5 w-9 rounded-full transition-colors"
                    :class="[
                      u.is_blocked ? 'bg-red-500' : 'bg-gray-300 dark:bg-gray-700',
                      togglingBlock === u.id ? 'opacity-50 pointer-events-none' : '',
                    ]"
                    :title="u.is_blocked ? `Compte bloqué${u.block_reason ? ' : ' + u.block_reason : ''}` : 'Compte actif'"
                    @click="toggleBlock(u)"
                  >
                    <span
                      class="absolute top-0.5 left-0.5 h-4 w-4 rounded-full bg-white shadow transition-transform"
                      :class="u.is_blocked ? 'translate-x-4' : 'translate-x-0'"
                    />
                  </button>
                  <span v-else class="text-xs text-gray-300 dark:text-gray-700">—</span>
                </td>
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
              <tr v-if="showBlockReason === u.id" :key="`${u.id}-block-reason`">
                <td colspan="13" class="px-4 py-3 bg-red-50 dark:bg-red-950/30 border-b border-red-100 dark:border-red-900/30">
                  <div class="flex items-center gap-3">
                    <span class="text-xs font-medium text-red-700 dark:text-red-300 whitespace-nowrap">Raison du blocage (optionnelle) :</span>
                    <input
                      v-model="blockReasonInput"
                      type="text"
                      maxlength="255"
                      placeholder="Ex : litige, compte piraté…"
                      class="flex-1 rounded border border-red-200 dark:border-red-800 bg-white dark:bg-gray-900 px-2 py-1 text-xs text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-1 focus:ring-red-400"
                    />
                    <button
                      class="px-2.5 py-1 text-xs rounded bg-red-600 text-white hover:bg-red-700 transition-colors"
                      :disabled="togglingBlock === u.id"
                      @click="toggleBlock(u)"
                    >
                      Confirmer le blocage
                    </button>
                    <button class="px-2.5 py-1 text-xs rounded text-gray-500 hover:text-gray-700 dark:hover:text-gray-300" @click="cancelBlock">
                      Annuler
                    </button>
                  </div>
                </td>
              </tr>
              <tr v-if="expanded === u.id" :key="`${u.id}-prefs`">
                <td colspan="13" class="px-4 py-3 bg-gray-50 dark:bg-gray-800/30">
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
      <div v-if="showCreate" class="fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-black/40" @click.self="showCreate = false">
        <div class="bg-white dark:bg-gray-900 rounded-t-2xl sm:rounded-xl shadow-xl w-full sm:max-w-md p-4 sm:p-6 border border-gray-200 dark:border-gray-800">
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
