<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth.store'
import { authService } from '@/services/auth.service'
import { activityService } from '@/services/activity.service'
import ActivityGrid from '@/components/activity/ActivityGrid.vue'
import ActivityHeatmap from '@/components/activity/ActivityHeatmap.vue'
import type { ActivityDay, ActivityHour } from '@/types'

const auth = useAuthStore()

// ── Avatar ────────────────────────────────────────────────────
const ACCEPTED = ['image/jpeg', 'image/png', 'image/gif', 'image/webp']
const MAX_BYTES = 2 * 1024 * 1024

const avatarUploading = ref(false)
const avatarError     = ref<string | null>(null)
const avatarDrag      = ref(false)

function validateAvatar(file: File): string | null {
  if (!ACCEPTED.includes(file.type)) return 'Format non supporté (JPG, PNG, GIF, WebP).'
  if (file.size > MAX_BYTES) return 'Fichier trop volumineux (max 2 Mo).'
  return null
}

async function handleAvatarFiles(files: FileList | null) {
  if (!files?.length) return
  avatarError.value = null
  const msg = validateAvatar(files[0]!)
  if (msg) { avatarError.value = msg; return }
  avatarUploading.value = true
  try {
    await auth.uploadAvatar(files[0]!)
  } catch {
    avatarError.value = 'Erreur lors de l\'upload.'
  } finally {
    avatarUploading.value = false
  }
}

function onAvatarInput(e: Event) {
  handleAvatarFiles((e.target as HTMLInputElement).files)
  ;(e.target as HTMLInputElement).value = ''
}

async function removeAvatar() {
  avatarError.value = null
  try {
    await auth.destroyAvatar()
  } catch {
    avatarError.value = 'Erreur lors de la suppression.'
  }
}

// ── Nom + Bio ─────────────────────────────────────────────────
const name        = ref(auth.user?.name ?? '')
const bio         = ref(auth.user?.bio ?? '')
const profileSaving = ref(false)
const profileSuccess = ref(false)
const profileError  = ref('')

async function saveProfile() {
  profileError.value = ''
  profileSuccess.value = false
  if (!name.value.trim()) { profileError.value = 'Le nom est requis.'; return }
  profileSaving.value = true
  try {
    await auth.updateProfile({ name: name.value.trim(), bio: bio.value.trim() || null })
    profileSuccess.value = true
    setTimeout(() => { profileSuccess.value = false }, 3000)
  } catch {
    profileError.value = 'Une erreur est survenue.'
  } finally {
    profileSaving.value = false
  }
}

// ── Changement de mot de passe ────────────────────────────────
const currentPassword = ref('')
const newPassword     = ref('')
const confirmPassword = ref('')
const pwdLoading      = ref(false)
const pwdSuccess      = ref(false)
const pwdErrors       = ref<Record<string, string[]>>({})
const pwdGlobalError  = ref('')

async function submitPasswordChange() {
  pwdErrors.value = {}
  pwdGlobalError.value = ''
  pwdSuccess.value = false
  if (newPassword.value !== confirmPassword.value) {
    pwdErrors.value = { password: ['La confirmation ne correspond pas.'] }
    return
  }
  pwdLoading.value = true
  try {
    await authService.updatePassword({
      current_password: currentPassword.value,
      password: newPassword.value,
      password_confirmation: confirmPassword.value,
    })
    pwdSuccess.value = true
    currentPassword.value = ''
    newPassword.value = ''
    confirmPassword.value = ''
  } catch (err: any) {
    const data = err?.response?.data
    if (data?.errors) pwdErrors.value = data.errors
    else pwdGlobalError.value = data?.message ?? 'Une erreur est survenue.'
  } finally {
    pwdLoading.value = false
  }
}

// ── Initiales ─────────────────────────────────────────────────
const initials = computed(() =>
  (auth.user?.name ?? '').split(' ').slice(0, 2).map(n => n[0]).join('').toUpperCase() || '?'
)

// ── Activité ──────────────────────────────────────────────────
const activityDays  = ref<ActivityDay[]>([])
const activityHours = ref<ActivityHour[]>([])
const activityLoading = ref(true)

onMounted(async () => {
  try {
    const data = await activityService.global()
    activityDays.value  = data.daily
    activityHours.value = data.hourly
  } finally {
    activityLoading.value = false
  }
})
</script>

<template>
  <div class="p-6 max-w-xl mx-auto space-y-6">
    <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-100">Profil</h1>

    <!-- ── Avatar ── -->
    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl p-6">
      <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-4">Photo de profil</h2>

      <div class="flex items-center gap-5">
        <!-- Avatar actuel ou initiales -->
        <div class="w-20 h-20 rounded-full overflow-hidden shrink-0 bg-brand-600 flex items-center justify-center">
          <img
            v-if="auth.user?.avatar_url"
            :src="auth.user.avatar_url"
            alt="avatar"
            class="w-full h-full object-cover"
          />
          <span v-else class="text-2xl font-semibold text-white">{{ initials }}</span>
        </div>

        <div class="flex-1 space-y-2">
          <label
            class="flex items-center gap-2 text-sm px-3 py-2 rounded-lg border cursor-pointer transition-colors w-fit"
            :class="avatarDrag
              ? 'border-brand-400 bg-brand-50 dark:bg-brand-900/20'
              : 'border-gray-300 dark:border-gray-600 hover:border-brand-400 hover:bg-gray-50 dark:hover:bg-gray-800/50'"
            @dragover.prevent="avatarDrag = true"
            @dragleave="avatarDrag = false"
            @drop.prevent="e => { avatarDrag = false; handleAvatarFiles(e.dataTransfer?.files ?? null) }"
          >
            <input
              type="file"
              accept="image/jpeg,image/png,image/gif,image/webp"
              class="sr-only"
              @change="onAvatarInput"
            />
            <svg v-if="avatarUploading" class="w-4 h-4 animate-spin text-brand-500" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
            </svg>
            <svg v-else class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
            </svg>
            <span class="text-gray-600 dark:text-gray-300">
              {{ avatarUploading ? 'Upload…' : 'Choisir une image' }}
            </span>
          </label>

          <button
            v-if="auth.user?.avatar_url"
            class="text-xs text-red-500 hover:text-red-700 transition-colors"
            @click="removeAvatar"
          >Supprimer la photo</button>

          <p class="text-xs text-gray-400">JPG, PNG, GIF, WebP · max 2 Mo</p>
          <p v-if="avatarError" class="text-xs text-red-500">{{ avatarError }}</p>
        </div>
      </div>
    </div>

    <!-- ── Informations ── -->
    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl p-6">
      <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-4">Informations</h2>

      <form class="space-y-4" @submit.prevent="saveProfile">
        <div>
          <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Nom</label>
          <input
            v-model="name"
            type="text"
            required
            class="w-full text-sm rounded-lg border border-gray-300 dark:border-gray-600
                   bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100
                   px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-500"
          />
        </div>

        <div>
          <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">
            E-mail
          </label>
          <input
            :value="auth.user?.email"
            type="email"
            disabled
            class="w-full text-sm rounded-lg border border-gray-200 dark:border-gray-700
                   bg-gray-50 dark:bg-gray-800/50 text-gray-500 dark:text-gray-500
                   px-3 py-2 cursor-not-allowed"
          />
        </div>

        <div>
          <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">
            À propos de l'auteur
            <span class="font-normal text-gray-400 ml-1">(visible dans les exports compilés)</span>
          </label>
          <textarea
            v-model="bio"
            rows="5"
            placeholder="Décris-toi en quelques mots : ton style d'écriture, tes inspirations, ton parcours…"
            class="w-full text-sm rounded-lg border border-gray-300 dark:border-gray-600
                   bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100
                   px-3 py-2 resize-none focus:outline-none focus:ring-2 focus:ring-brand-500"
          />
          <p class="text-xs text-gray-400 mt-1">{{ bio.length }}/2000</p>
        </div>

        <p v-if="profileError" class="text-xs text-red-500">{{ profileError }}</p>

        <Transition name="fade">
          <p v-if="profileSuccess" class="text-xs text-green-600 dark:text-green-400 font-medium">
            Profil mis à jour.
          </p>
        </Transition>

        <button
          type="submit"
          :disabled="profileSaving"
          class="px-4 py-2 text-sm font-medium rounded-lg bg-brand-600 text-white
                 hover:bg-brand-700 disabled:opacity-50 transition-colors"
        >{{ profileSaving ? 'Enregistrement…' : 'Sauvegarder' }}</button>
      </form>
    </div>

    <!-- ── Activité ── -->
    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl p-6">
      <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-5">Activité d'écriture</h2>

      <div v-if="activityLoading" class="text-xs text-gray-400">Chargement…</div>
      <template v-else>
        <div class="mb-6">
          <p class="text-xs text-gray-400 mb-3">365 derniers jours</p>
          <ActivityGrid :days="activityDays" />
        </div>
        <div>
          <p class="text-xs text-gray-400 mb-3">Habitudes par heure et jour de semaine</p>
          <ActivityHeatmap :hours="activityHours" />
        </div>
      </template>
    </div>

    <!-- ── Mot de passe ── -->
    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl p-6">
      <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-4">
        Changer le mot de passe
      </h2>

      <form class="space-y-4" @submit.prevent="submitPasswordChange">
        <div>
          <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">
            Mot de passe actuel
          </label>
          <input
            v-model="currentPassword"
            type="password"
            autocomplete="current-password"
            required
            class="w-full text-sm rounded-lg border px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-500"
            :class="pwdErrors.current_password
              ? 'border-red-400 bg-red-50 dark:bg-red-900/10'
              : 'border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100'"
          />
          <p v-if="pwdErrors.current_password" class="mt-1 text-xs text-red-500">
            {{ pwdErrors.current_password[0] }}
          </p>
        </div>

        <div>
          <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">
            Nouveau mot de passe
          </label>
          <input
            v-model="newPassword"
            type="password"
            autocomplete="new-password"
            required
            minlength="8"
            class="w-full text-sm rounded-lg border px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-500"
            :class="pwdErrors.password
              ? 'border-red-400 bg-red-50 dark:bg-red-900/10'
              : 'border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100'"
          />
          <p v-if="pwdErrors.password" class="mt-1 text-xs text-red-500">
            {{ pwdErrors.password[0] }}
          </p>
        </div>

        <div>
          <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">
            Confirmer le nouveau mot de passe
          </label>
          <input
            v-model="confirmPassword"
            type="password"
            autocomplete="new-password"
            required
            class="w-full text-sm rounded-lg border px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-500"
            :class="pwdErrors.password
              ? 'border-red-400 bg-red-50 dark:bg-red-900/10'
              : 'border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100'"
          />
        </div>

        <p v-if="pwdGlobalError" class="text-xs text-red-500">{{ pwdGlobalError }}</p>

        <Transition name="fade">
          <p v-if="pwdSuccess" class="text-xs text-green-600 dark:text-green-400 font-medium">
            Mot de passe mis à jour.
          </p>
        </Transition>

        <button
          type="submit"
          :disabled="pwdLoading"
          class="px-4 py-2 text-sm font-medium rounded-lg bg-brand-600 text-white
                 hover:bg-brand-700 disabled:opacity-50 transition-colors"
        >{{ pwdLoading ? 'Enregistrement…' : 'Mettre à jour' }}</button>
      </form>
    </div>
  </div>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.3s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
