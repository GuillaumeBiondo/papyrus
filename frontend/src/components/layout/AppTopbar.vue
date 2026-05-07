<script setup lang="ts">
import { ref, computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth.store'
import { useThemeStore } from '@/stores/theme.store'
import { useEditorStore } from '@/stores/editor.store'
import { useNotebookStore } from '@/stores/notebook.store'
import BugReportButton from '@/components/layout/BugReportButton.vue'
import { useChangelogStore } from '@/stores/changelog.store'

const router = useRouter()
const route = useRoute()
const auth = useAuthStore()
const theme = useThemeStore()
const editor = useEditorStore()
const notebook = useNotebookStore()

const changelog = useChangelogStore()

const dropdownOpen = ref(false)

const themeLabel = computed(() => {
  if (theme.theme === 'light') return 'Clair'
  if (theme.theme === 'dark') return 'Sombre'
  return 'Système'
})

const initials = computed(() =>
  auth.user?.name.slice(0, 2).toUpperCase() ?? '??',
)

async function logout() {
  dropdownOpen.value = false
  await auth.logout()
  router.push({ name: 'login' })
}

function closeOnOutside(e: MouseEvent) {
  const el = (e.target as HTMLElement).closest('[data-dropdown]')
  if (!el) dropdownOpen.value = false
}
</script>

<template>
  <header
    class="flex items-center h-12 px-4 border-b border-gray-300 dark:border-gray-800
           bg-[#f5f4f1] dark:bg-[#0c0b18] shrink-0"
    @click="closeOnOutside"
  >
    <!-- Logo -->
    <RouterLink to="/dashboard" class="font-semibold text-brand-600 dark:text-brand-400">
      Papyrus
    </RouterLink>

    <!-- Titre contextuel -->
    <span class="ml-4 text-sm text-gray-500 truncate max-w-xs">
      <template v-if="editor.currentProject">{{ editor.currentProject.title }}</template>
      <template v-else-if="route.name === 'dashboard'">Mes romans</template>
    </span>

    <div class="ml-auto flex items-center gap-2">
      <!-- Bug report -->
      <BugReportButton />

      <!-- Changelog badge -->
      <button
        v-if="changelog.loaded"
        class="relative btn-ghost transition-colors"
        :class="changelog.unreadCount > 0 ? 'text-gray-600 dark:text-gray-300' : 'text-gray-400 dark:text-gray-600'"
        title="Nouveautés"
        @click="changelog.openModal()"
      >
        <svg
          class="w-4 h-4"
          :class="changelog.unreadCount > 0 ? 'bell-ring' : ''"
          fill="none" stroke="currentColor" viewBox="0 0 24 24"
        >
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
        </svg>
        <span
          v-if="changelog.unreadCount > 0"
          class="absolute -top-1 -right-1 w-4 h-4 rounded-full bg-red-500 text-white text-[10px] font-bold flex items-center justify-center leading-none"
        >
          {{ changelog.unreadCount > 9 ? '9+' : changelog.unreadCount }}
        </span>
      </button>

      <!-- Carnet -->
      <button class="btn-ghost text-gray-600 dark:text-gray-300" @click="notebook.toggleDrawer()">
        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477
                   5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5
                   c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18
                   c-1.746 0-3.332.477-4.5 1.253" />
        </svg>
        Carnet
      </button>

      <!-- Avatar dropdown -->
      <div class="relative" data-dropdown>
        <button
          class="flex items-center gap-2 rounded-full focus:outline-none"
          @click="dropdownOpen = !dropdownOpen"
        >
          <span
            class="w-8 h-8 rounded-full bg-brand-600 text-white text-xs font-medium
                   flex items-center justify-center"
          >
            {{ initials }}
          </span>
        </button>

        <Transition name="dropdown">
          <div
            v-if="dropdownOpen"
            class="absolute right-0 top-10 w-52 bg-white dark:bg-gray-900
                   border border-gray-300 dark:border-gray-700
                   rounded-lg shadow-lg z-50 py-1"
          >
            <div class="px-3 py-2 border-b border-gray-200 dark:border-gray-800">
              <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
                {{ auth.user?.name }}
              </p>
              <p class="text-xs text-gray-500">{{ auth.user?.email }}</p>
            </div>

            <RouterLink
              to="/profile"
              class="dropdown-item text-gray-700 dark:text-gray-300"
              @click="dropdownOpen = false"
            >
              Profil
            </RouterLink>
            <RouterLink
              to="/settings"
              class="dropdown-item text-gray-700 dark:text-gray-300"
              @click="dropdownOpen = false"
            >
              Paramètres
            </RouterLink>

            <div class="border-t border-gray-200 dark:border-gray-800 my-1" />

            <button
              class="dropdown-item w-full text-left flex items-center gap-2
                     text-gray-700 dark:text-gray-300"
              @click="theme.cycleTheme()"
            >
              <svg v-if="theme.theme === 'light'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364-6.364l-.707.707
                         M6.343 17.657l-.707.707m12.728 0l-.707-.707M6.343 6.343l-.707-.707
                         M12 7a5 5 0 100 10A5 5 0 0012 7z" />
              </svg>
              <svg v-else-if="theme.theme === 'dark'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003
                         9.003 0 008.354-5.646z" />
              </svg>
              <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17H3a2 2 0 01-2-2V5
                         a2 2 0 012-2h14a2 2 0 012 2v10a2 2 0 01-2 2h-2" />
              </svg>
              {{ themeLabel }}
            </button>

            <div class="border-t border-gray-200 dark:border-gray-800 my-1" />

            <button
              class="dropdown-item w-full text-left text-red-600 dark:text-red-400"
              @click="logout"
            >
              Déconnexion
            </button>
          </div>
        </Transition>
      </div>
    </div>
  </header>
</template>

<style scoped>
.dropdown-enter-active,
.dropdown-leave-active {
  transition: opacity 0.1s, transform 0.1s;
}
.dropdown-enter-from,
.dropdown-leave-to {
  opacity: 0;
  transform: translateY(-4px);
}

@keyframes bell-ring {
  0%, 100% { transform: rotate(0deg); }
  10%       { transform: rotate(14deg); }
  20%       { transform: rotate(-10deg); }
  30%       { transform: rotate(12deg); }
  40%       { transform: rotate(-8deg); }
  50%       { transform: rotate(8deg); }
  60%       { transform: rotate(-4deg); }
  70%       { transform: rotate(4deg); }
  80%       { transform: rotate(-2deg); }
}

.bell-ring {
  animation: bell-ring 2.5s ease-in-out infinite;
  transform-origin: top center;
}
</style>
