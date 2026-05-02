<script setup lang="ts">
import { ref } from 'vue'
import { authService } from '@/services/auth.service'

const currentPassword = ref('')
const newPassword = ref('')
const confirmPassword = ref('')

const loading = ref(false)
const success = ref(false)
const errors = ref<Record<string, string[]>>({})
const globalError = ref('')

async function submitPasswordChange() {
  errors.value = {}
  globalError.value = ''
  success.value = false

  if (newPassword.value !== confirmPassword.value) {
    errors.value = { password: ['La confirmation du mot de passe ne correspond pas.'] }
    return
  }

  loading.value = true
  try {
    await authService.updatePassword({
      current_password: currentPassword.value,
      password: newPassword.value,
      password_confirmation: confirmPassword.value,
    })
    success.value = true
    currentPassword.value = ''
    newPassword.value = ''
    confirmPassword.value = ''
  } catch (err: any) {
    const data = err?.response?.data
    if (data?.errors) {
      errors.value = data.errors
    } else {
      globalError.value = data?.message ?? 'Une erreur est survenue.'
    }
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="p-6 max-w-lg mx-auto">
    <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-6">Paramètres</h1>

    <div class="bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-xl p-6">
      <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-4">Changer le mot de passe</h2>

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
            class="w-full text-sm rounded-lg border px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-500 transition-colors"
            :class="errors.current_password
              ? 'border-red-400 dark:border-red-500 bg-red-50 dark:bg-red-900/10'
              : 'border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100'"
          />
          <p v-if="errors.current_password" class="mt-1 text-xs text-red-500">
            {{ errors.current_password[0] }}
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
            class="w-full text-sm rounded-lg border px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-500 transition-colors"
            :class="errors.password
              ? 'border-red-400 dark:border-red-500 bg-red-50 dark:bg-red-900/10'
              : 'border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100'"
          />
          <p v-if="errors.password" class="mt-1 text-xs text-red-500">
            {{ errors.password[0] }}
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
            class="w-full text-sm rounded-lg border px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-500 transition-colors"
            :class="errors.password
              ? 'border-red-400 dark:border-red-500 bg-red-50 dark:bg-red-900/10'
              : 'border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100'"
          />
        </div>

        <p v-if="globalError" class="text-xs text-red-500">{{ globalError }}</p>

        <Transition name="fade">
          <p v-if="success" class="text-xs text-green-600 dark:text-green-400 font-medium">
            Mot de passe mis à jour avec succès.
          </p>
        </Transition>

        <div class="pt-1">
          <button
            type="submit"
            :disabled="loading"
            class="px-4 py-2 text-sm font-medium rounded-lg bg-brand-600 text-white hover:bg-brand-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
          >
            {{ loading ? 'Enregistrement…' : 'Mettre à jour' }}
          </button>
        </div>

      </form>
    </div>
  </div>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.3s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
