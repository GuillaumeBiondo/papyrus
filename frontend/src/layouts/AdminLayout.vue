<script setup lang="ts">
import { computed, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth.store'
import { useThemeStore } from '@/stores/theme.store'

const router = useRouter()
const route = useRoute()
const auth = useAuthStore()
const theme = useThemeStore()

const mobileMenuOpen = ref(false)
const sidebarCollapsed = ref(false)
const showDrawer = ref(false)

function isActive(name: string): boolean {
  return route.name === name
}

const nav = [
  { name: 'admin-dashboard',        label: 'Tableau de bord',  short: 'Accueil',    icon: 'chart' },
  { name: 'admin-users',            label: 'Utilisateurs',     short: 'Comptes',    icon: 'users' },
  { name: 'admin-content-types',    label: 'Types de contenu', short: 'Contenus',   icon: 'layers' },
  { name: 'admin-changelogs',       label: 'Journaux',         short: 'Journaux',   icon: 'log' },
  { name: 'admin-fonts',            label: 'Polices',          short: 'Polices',    icon: 'font' },
  { name: 'admin-ai-verifications', label: 'Révisions IA',    short: 'Révisions',  icon: 'ai' },
  { name: 'admin-ai-enrich',        label: 'Dictionnaire IA',  short: 'Dico IA',    icon: 'book' },
  { name: 'admin-workshops',        label: 'Ateliers',         short: 'Ateliers',   icon: 'workshop' },
  { name: 'admin-settings',         label: 'Paramètres',       short: 'Réglages',   icon: 'settings' },
]

const bottomNav = [nav[0]!, nav[1]!, nav[3]!, nav[8]!]

const currentPageLabel = computed(() => nav.find(n => n.name === route.name)?.label ?? 'Admin')

const initials = computed(() => auth.user?.name.slice(0, 2).toUpperCase() ?? '??')

function navigate(name: string) {
  mobileMenuOpen.value = false
  router.push({ name })
}

async function logout() {
  mobileMenuOpen.value = false
  await auth.logout()
  router.push({ name: 'login' })
}
</script>

<template>
  <div class="flex h-screen bg-[#f5f4f1] dark:bg-[#0c0b18]">

    <!-- Mobile header -->
    <header class="md:hidden fixed top-0 inset-x-0 z-40 h-12 flex items-center px-4 gap-3
                   bg-white dark:bg-[var(--ui-sidebar-bg)] border-b border-gray-200 dark:border-gray-800">
      <button
        class="p-1.5 rounded-md text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
        @click="mobileMenuOpen = true"
      >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
        </svg>
      </button>
      <span class="font-semibold text-brand-600 dark:text-brand-400">Papyrus</span>
      <span class="text-xs text-gray-400 uppercase tracking-widest">admin</span>
      <span class="mx-1 text-gray-300 dark:text-gray-600">·</span>
      <span class="text-sm text-gray-600 dark:text-gray-400 truncate">{{ currentPageLabel }}</span>
    </header>

    <!-- Mobile drawer backdrop -->
    <Transition name="fade">
      <div
        v-if="mobileMenuOpen"
        class="md:hidden fixed inset-0 z-50 bg-black/40"
        @click="mobileMenuOpen = false"
      />
    </Transition>

    <!-- Mobile drawer -->
    <Transition name="slide">
      <aside
        v-if="mobileMenuOpen"
        class="md:hidden fixed top-0 left-0 bottom-0 z-50 w-72 flex flex-col
               bg-white dark:bg-[var(--ui-sidebar-bg)] shadow-xl"
      >
        <!-- Drawer header -->
        <div class="h-12 flex items-center justify-between px-4 border-b border-gray-200 dark:border-gray-800 shrink-0">
          <div class="flex items-center gap-2">
            <span class="font-semibold text-brand-600 dark:text-brand-400">Papyrus</span>
            <span class="text-xs text-gray-400 uppercase tracking-widest">admin</span>
          </div>
          <button
            class="p-1.5 rounded-md text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
            @click="mobileMenuOpen = false"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
          </button>
        </div>

        <!-- Drawer nav -->
        <nav class="flex-1 py-3 space-y-0.5 px-2 overflow-y-auto">
          <button
            v-for="item in nav"
            :key="item.name"
            class="w-full flex items-center gap-2.5 px-3 py-2.5 rounded-md text-sm transition-colors"
            :class="isActive(item.name)
              ? 'bg-brand-50 dark:bg-brand-950 text-brand-700 dark:text-brand-300 font-medium'
              : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-900'"
            @click="navigate(item.name)"
          >
            <!-- Same icons block as desktop (reused below) -->
            <svg v-if="item.icon === 'chart'" class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
            <svg v-else-if="item.icon === 'users'" class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
            <svg v-else-if="item.icon === 'layers'" class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
            <svg v-else-if="item.icon === 'log'" class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            <svg v-else-if="item.icon === 'font'" class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6M9 16h6M7 8h10M5 4h14a2 2 0 012 2v12a2 2 0 01-2 2H5a2 2 0 01-2-2V6a2 2 0 012-2z"/></svg>
            <svg v-else-if="item.icon === 'ai'" class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 3.104v5.714a2.25 2.25 0 01-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 014.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19.8 15.3M14.25 3.104c.251.023.501.05.75.082M19.8 15.3l-1.57.393A9.065 9.065 0 0112 15a9.065 9.065 0 00-6.23-.693L5 14.5m14.8.8l1.402 1.402c1 1 .03 2.798-1.345 2.798H4.543c-1.376 0-2.345-1.798-1.345-2.798L4.2 15.3"/></svg>
            <svg v-else-if="item.icon === 'book'" class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
            <svg v-else-if="item.icon === 'workshop'" class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/></svg>
            <svg v-else-if="item.icon === 'settings'" class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            {{ item.label }}
          </button>
        </nav>

        <!-- Drawer footer -->
        <div class="border-t border-gray-200 dark:border-gray-800 p-3 space-y-1 shrink-0">
          <div class="flex items-center gap-2 px-2 py-1">
            <span class="w-7 h-7 rounded-full bg-brand-600 text-white text-xs font-medium flex items-center justify-center shrink-0">
              {{ initials }}
            </span>
            <span class="text-xs text-gray-600 dark:text-gray-400 truncate">{{ auth.user?.name }}</span>
          </div>
          <button
            class="w-full flex items-center gap-2 px-2 py-1.5 rounded text-xs text-gray-500 dark:text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-900 transition-colors"
            @click="theme.cycleTheme()"
          >
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
            Thème
          </button>
          <button
            class="w-full flex items-center gap-2 px-2 py-1.5 rounded text-xs text-red-500 hover:bg-red-50 dark:hover:bg-red-950 transition-colors"
            @click="logout"
          >
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
            Déconnexion
          </button>
        </div>
      </aside>
    </Transition>

    <!-- Sidebar (desktop) -->
    <aside class="hidden md:flex w-56 shrink-0 flex-col border-r border-gray-200 dark:border-gray-800
                  bg-white dark:bg-[var(--ui-sidebar-bg)]">
      <!-- Logo -->
      <div class="h-12 flex items-center px-4 border-b border-gray-200 dark:border-gray-800">
        <span class="font-semibold text-brand-600 dark:text-brand-400">Papyrus</span>
        <span class="ml-2 text-xs text-gray-400 dark:text-gray-600 uppercase tracking-widest">admin</span>
      </div>

      <!-- Nav -->
      <nav class="flex-1 py-3 space-y-0.5 overflow-y-auto" :class="sidebarCollapsed ? 'px-1' : 'px-2'">
        <RouterLink
          v-for="item in nav"
          :key="item.name"
          :to="{ name: item.name }"
          class="flex items-center rounded-md text-sm transition-colors"
          :class="[
            sidebarCollapsed ? 'justify-center px-2 py-2.5' : 'gap-2.5 px-3 py-2',
            isActive(item.name)
              ? 'bg-brand-50 dark:bg-brand-950 text-brand-700 dark:text-brand-300 font-medium'
              : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-900',
          ]"
          :title="sidebarCollapsed ? item.label : undefined"
        >
          <svg v-if="item.icon === 'chart'" class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
          </svg>
          <svg v-else-if="item.icon === 'users'" class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
          </svg>
          <svg v-else-if="item.icon === 'layers'" class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5" />
          </svg>
          <svg v-else-if="item.icon === 'log'" class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
          </svg>
          <svg v-else-if="item.icon === 'font'" class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6M9 16h6M7 8h10M5 4h14a2 2 0 012 2v12a2 2 0 01-2 2H5a2 2 0 01-2-2V6a2 2 0 012-2z" />
          </svg>
          <svg v-else-if="item.icon === 'ai'" class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 3.104v5.714a2.25 2.25 0 01-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 014.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19.8 15.3M14.25 3.104c.251.023.501.05.75.082M19.8 15.3l-1.57.393A9.065 9.065 0 0112 15a9.065 9.065 0 00-6.23-.693L5 14.5m14.8.8l1.402 1.402c1 1 .03 2.798-1.345 2.798H4.543c-1.376 0-2.345-1.798-1.345-2.798L4.2 15.3" />
          </svg>
          <svg v-else-if="item.icon === 'book'" class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
          </svg>
          <svg v-else-if="item.icon === 'workshop'" class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
          </svg>
          <svg v-else-if="item.icon === 'settings'" class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
          </svg>

          <span v-if="!sidebarCollapsed">{{ item.label }}</span>
        </RouterLink>
      </nav>

      <!-- Footer -->
      <div
        class="border-t border-gray-200 dark:border-gray-800 shrink-0"
        :class="sidebarCollapsed ? 'p-2 flex flex-col items-center gap-1' : 'p-3 space-y-1'"
      >
        <div :class="sidebarCollapsed ? 'flex justify-center py-1' : 'flex items-center gap-2 px-2 py-1'">
          <span class="w-7 h-7 rounded-full bg-brand-600 text-white text-xs font-medium flex items-center justify-center shrink-0">
            {{ initials }}
          </span>
          <span v-if="!sidebarCollapsed" class="text-xs text-gray-600 dark:text-gray-400 truncate">{{ auth.user?.name }}</span>
        </div>
        <button
          class="transition-colors"
          :class="sidebarCollapsed
            ? 'p-2 rounded text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-900'
            : 'w-full flex items-center gap-2 px-2 py-1.5 rounded text-xs text-gray-500 dark:text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-900'"
          :title="sidebarCollapsed ? 'Changer de thème' : undefined"
          @click="theme.cycleTheme()"
        >
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
          </svg>
          <span v-if="!sidebarCollapsed" class="text-xs">Thème</span>
        </button>
        <button
          class="transition-colors"
          :class="sidebarCollapsed
            ? 'p-2 rounded text-red-500 hover:bg-red-50 dark:hover:bg-red-950'
            : 'w-full flex items-center gap-2 px-2 py-1.5 rounded text-xs text-red-500 hover:bg-red-50 dark:hover:bg-red-950'"
          :title="sidebarCollapsed ? 'Déconnexion' : undefined"
          @click="logout"
        >
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
          </svg>
          <span v-if="!sidebarCollapsed" class="text-xs">Déconnexion</span>
        </button>
      </div>
    </aside>

    <!-- Main content -->
    <main class="flex-1 overflow-y-auto pt-12 md:pt-0">
      <RouterView />
    </main>

    <!-- ===== MOBILE BOTTOM NAV ===== -->
    <nav class="md:hidden fixed bottom-0 inset-x-0 z-40 bg-white dark:bg-[var(--ui-sidebar-bg)] border-t border-gray-200 dark:border-gray-800">
      <div class="flex items-center justify-around h-16 px-1">
        <RouterLink
          v-for="item in bottomNav"
          :key="item.name"
          :to="{ name: item.name }"
          class="flex flex-col items-center gap-0.5 px-2 py-2 rounded-xl transition-colors min-w-0 flex-1"
          :class="isActive(item.name)
            ? 'text-brand-600 dark:text-brand-400'
            : 'text-gray-400 dark:text-gray-500'"
        >
          <svg v-if="item.icon === 'chart'" class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
          </svg>
          <svg v-else-if="item.icon === 'users'" class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
          </svg>
          <svg v-else-if="item.icon === 'log'" class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
          </svg>
          <svg v-else-if="item.icon === 'settings'" class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
          </svg>
          <span class="text-[10px] font-medium leading-tight truncate w-full text-center">{{ item.short }}</span>
        </RouterLink>

        <!-- More button -->
        <button
          class="flex flex-col items-center gap-0.5 px-2 py-2 rounded-xl transition-colors min-w-0 flex-1"
          :class="showDrawer ? 'text-brand-600 dark:text-brand-400' : 'text-gray-400 dark:text-gray-500'"
          @click="showDrawer = !showDrawer"
        >
          <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7" />
          </svg>
          <span class="text-[10px] font-medium">Plus</span>
        </button>
      </div>
    </nav>

    <!-- ===== MOBILE DRAWER ===== -->
    <Transition name="drawer">
      <div v-if="showDrawer" class="md:hidden fixed inset-0 z-50 flex flex-col justify-end">
        <div class="absolute inset-0 bg-black/40" @click="showDrawer = false" />
        <div class="sheet relative bg-white dark:bg-gray-900 rounded-t-2xl border-t border-gray-200 dark:border-gray-800 p-4 pb-8">
          <!-- Handle -->
          <div class="w-10 h-1 bg-gray-300 dark:bg-gray-700 rounded-full mx-auto mb-5" />

          <!-- All nav as 3-column grid -->
          <div class="grid grid-cols-3 gap-2 mb-4">
            <RouterLink
              v-for="item in nav"
              :key="item.name"
              :to="{ name: item.name }"
              class="flex flex-col items-center gap-1.5 p-3 rounded-xl transition-colors"
              :class="isActive(item.name)
                ? 'bg-brand-50 dark:bg-brand-950 text-brand-700 dark:text-brand-300'
                : 'text-gray-600 dark:text-gray-400 active:bg-gray-100 dark:active:bg-gray-800'"
              @click="showDrawer = false"
            >
              <svg v-if="item.icon === 'chart'" class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
              </svg>
              <svg v-else-if="item.icon === 'users'" class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
              </svg>
              <svg v-else-if="item.icon === 'layers'" class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5" />
              </svg>
              <svg v-else-if="item.icon === 'log'" class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
              <svg v-else-if="item.icon === 'font'" class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6M9 16h6M7 8h10M5 4h14a2 2 0 012 2v12a2 2 0 01-2 2H5a2 2 0 01-2-2V6a2 2 0 012-2z" />
              </svg>
              <svg v-else-if="item.icon === 'ai'" class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 3.104v5.714a2.25 2.25 0 01-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 014.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19.8 15.3M14.25 3.104c.251.023.501.05.75.082M19.8 15.3l-1.57.393A9.065 9.065 0 0112 15a9.065 9.065 0 00-6.23-.693L5 14.5m14.8.8l1.402 1.402c1 1 .03 2.798-1.345 2.798H4.543c-1.376 0-2.345-1.798-1.345-2.798L4.2 15.3" />
              </svg>
              <svg v-else-if="item.icon === 'book'" class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
              </svg>
              <svg v-else-if="item.icon === 'workshop'" class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
              </svg>
              <svg v-else-if="item.icon === 'settings'" class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
              </svg>
              <span class="text-xs text-center leading-tight">{{ item.short }}</span>
            </RouterLink>
          </div>

          <!-- User + actions -->
          <div class="border-t border-gray-100 dark:border-gray-800 pt-3 flex items-center justify-between">
            <div class="flex items-center gap-2.5">
              <span class="w-8 h-8 rounded-full bg-brand-600 text-white text-xs font-medium flex items-center justify-center shrink-0">
                {{ initials }}
              </span>
              <span class="text-sm font-medium text-gray-700 dark:text-gray-300 truncate max-w-[140px]">{{ auth.user?.name }}</span>
            </div>
            <div class="flex gap-1.5">
              <button
                class="p-2.5 rounded-xl text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
                title="Changer de thème"
                @click="theme.cycleTheme()"
              >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                </svg>
              </button>
              <button
                class="p-2.5 rounded-xl text-red-500 hover:bg-red-50 dark:hover:bg-red-950 transition-colors"
                title="Déconnexion"
                @click="logout"
              >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                </svg>
              </button>
            </div>
          </div>
        </div>
      </div>
    </Transition>
  </div>
</template>

<style scoped>
.drawer-enter-active,
.drawer-leave-active { transition: opacity 0.2s ease; }
.drawer-enter-from,
.drawer-leave-to { opacity: 0; }

.drawer-enter-active .sheet { transition: transform 0.28s cubic-bezier(0.32, 0.72, 0, 1); }
.drawer-leave-active .sheet { transition: transform 0.2s ease-in; }
.drawer-enter-from .sheet,
.drawer-leave-to .sheet { transform: translateY(100%); }
</style>
