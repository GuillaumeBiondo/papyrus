<script setup lang="ts">
import { ref, computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth.store'
import { useEditorStore } from '@/stores/editor.store'
import { useNotebookStore } from '@/stores/notebook.store'
import BugReportButton from '@/components/layout/BugReportButton.vue'
import { useChangelogStore } from '@/stores/changelog.store'

const router   = useRouter()
const route    = useRoute()
const auth     = useAuthStore()
const editor   = useEditorStore()
const notebook = useNotebookStore()
const changelog = useChangelogStore()

const latestChangelog = computed(() => changelog.all[0] ?? null)
const latestIsUnread  = computed(() => latestChangelog.value ? !latestChangelog.value.read : false)

const dropdownOpen = ref(false)

const initials = computed(() =>
  auth.user?.name.split(' ').slice(0, 2).map(n => n[0]).join('').toUpperCase() ?? '??',
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

      <!-- Dernier changelog -->
      <button
        v-if="latestChangelog"
        class="flex items-center gap-1.5 px-2 py-1 rounded-lg transition-colors hover:bg-gray-100 dark:hover:bg-gray-800"
        :class="latestIsUnread ? 'text-gray-700 dark:text-gray-200' : 'text-gray-400 dark:text-gray-600'"
        title="Nouveautés"
        @click="changelog.openModal()"
      >
        <svg
          class="w-3.5 h-3.5 shrink-0"
          :class="[latestIsUnread ? 'bell-ring text-red-500' : '', 'transition-colors']"
          fill="none" stroke="currentColor" viewBox="0 0 24 24"
        >
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
        </svg>
        <span v-if="latestChangelog.version" class="text-[10px] font-mono font-semibold"
              :class="latestIsUnread ? 'text-red-500' : 'text-gray-400 dark:text-gray-600'">
          v{{ latestChangelog.version }}
        </span>
        <span class="text-xs truncate max-w-[140px] hidden sm:inline">{{ latestChangelog.title }}</span>
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
          <div class="w-8 h-8 rounded-full overflow-hidden shrink-0">
            <img
              v-if="auth.user?.avatar_url"
              :src="auth.user.avatar_url"
              :alt="auth.user?.name"
              class="w-full h-full object-cover"
            />
            <span
              v-else
              class="w-full h-full flex items-center justify-center bg-brand-600 text-white text-xs font-medium"
            >{{ initials }}</span>
          </div>
        </button>

        <Transition name="dropdown">
          <div
            v-if="dropdownOpen"
            class="absolute right-0 top-10 w-52 bg-white dark:bg-gray-900
                   border border-gray-300 dark:border-gray-700
                   rounded-lg shadow-lg z-50 py-1"
          >
            <div class="px-3 py-2 border-b border-gray-200 dark:border-gray-800 flex items-center gap-2.5">
              <div class="w-8 h-8 rounded-full overflow-hidden shrink-0">
                <img
                  v-if="auth.user?.avatar_url"
                  :src="auth.user.avatar_url"
                  class="w-full h-full object-cover"
                />
                <span
                  v-else
                  class="w-full h-full flex items-center justify-center bg-brand-600 text-white text-xs font-medium"
                >{{ initials }}</span>
              </div>
              <div class="min-w-0">
                <p class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">
                  {{ auth.user?.name }}
                </p>
                <p class="text-xs text-gray-500 truncate">{{ auth.user?.email }}</p>
              </div>
            </div>

            <RouterLink
              to="/profile"
              class="dropdown-item text-gray-700 dark:text-gray-300"
              @click="dropdownOpen = false"
            >Profil</RouterLink>

            <RouterLink
              to="/settings"
              class="dropdown-item text-gray-700 dark:text-gray-300"
              @click="dropdownOpen = false"
            >Paramètres</RouterLink>

            <div class="border-t border-gray-200 dark:border-gray-800 my-1" />

            <button
              class="dropdown-item w-full text-left text-red-600 dark:text-red-400"
              @click="logout"
            >Déconnexion</button>
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
