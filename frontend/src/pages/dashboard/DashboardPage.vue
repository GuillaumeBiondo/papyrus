<script setup lang="ts">
import { onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useProjectsStore } from '@/stores/projects.store'

const router = useRouter()
const projects = useProjectsStore()

onMounted(() => projects.fetchAll())
</script>

<template>
  <div class="p-6 max-w-5xl mx-auto">
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-100">Mes projets</h1>
      <button
        class="bg-brand-600 hover:bg-brand-800 text-white text-sm rounded-lg px-4 py-2 transition-colors"
        @click="router.push({ name: 'editor' })"
      >
        + Nouveau projet
      </button>
    </div>

    <div v-if="projects.loading" class="text-sm text-gray-400">Chargement…</div>

    <div v-else-if="!projects.projects.length" class="text-center py-16">
      <p class="text-gray-400 text-sm">Aucun projet. Crée ton premier roman !</p>
    </div>

    <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
      <div
        v-for="project in projects.projects"
        :key="project.id"
        class="border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden
               hover:shadow-md transition-shadow cursor-pointer bg-white dark:bg-gray-900"
        @click="router.push({ name: 'editor', params: { projectId: project.id } })"
      >
        <!-- Bande couleur -->
        <div
          class="h-1.5"
          :style="{ backgroundColor: project.color ?? '#534AB7' }"
        />
        <div class="p-4">
          <h2 class="font-medium text-gray-900 dark:text-gray-100 truncate">{{ project.title }}</h2>
          <p v-if="project.genre" class="text-xs text-gray-400 mt-0.5">{{ project.genre }}</p>
          <div class="flex items-center gap-3 mt-3">
            <RouterLink
              :to="{ name: 'editor', params: { projectId: project.id } }"
              class="text-xs text-brand-600 dark:text-brand-400 hover:underline"
              @click.stop
            >
              Éditeur
            </RouterLink>
            <RouterLink
              :to="{ name: 'cards', params: { projectId: project.id } }"
              class="text-xs text-gray-500 hover:underline"
              @click.stop
            >
              Fiches
            </RouterLink>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
