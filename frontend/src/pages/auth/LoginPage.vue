<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth.store'

const router = useRouter()
const auth = useAuthStore()

const email = ref('')
const password = ref('')
const error = ref('')
const loading = ref(false)

async function submit() {
  error.value = ''
  loading.value = true
  try {
    await auth.login(email.value, password.value)
    router.push({ name: 'dashboard' })
  } catch {
    error.value = 'Identifiants incorrects.'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="w-full max-w-sm">
    <div class="text-center mb-8">
      <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">Papyrus</h1>
      <p class="text-sm text-gray-500 mt-1">Connecte-toi à ton espace</p>
    </div>

    <form
      class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700
             rounded-xl p-6 shadow-sm"
      @submit.prevent="submit"
    >
      <div class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
            Email
          </label>
          <input
            v-model="email"
            type="email"
            required
            autocomplete="email"
            class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2
                   text-sm bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100
                   focus:outline-none focus:ring-2 focus:ring-brand-400"
          />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
            Mot de passe
          </label>
          <input
            v-model="password"
            type="password"
            required
            autocomplete="current-password"
            class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2
                   text-sm bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100
                   focus:outline-none focus:ring-2 focus:ring-brand-400"
          />
        </div>

        <p v-if="error" class="text-sm text-red-500">{{ error }}</p>

        <button
          type="submit"
          :disabled="loading"
          class="w-full bg-brand-600 hover:bg-brand-800 text-white rounded-lg py-2
                 text-sm font-medium transition-colors disabled:opacity-50"
        >
          {{ loading ? 'Connexion…' : 'Se connecter' }}
        </button>
      </div>
    </form>
  </div>
</template>
