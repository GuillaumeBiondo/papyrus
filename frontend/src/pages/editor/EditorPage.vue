<script setup lang="ts">
import { onMounted, onUnmounted } from 'vue'
import { useRoute } from 'vue-router'
import { useEditorStore } from '@/stores/editor.store'

const route = useRoute()
const editor = useEditorStore()

onMounted(() => {
  editor.loadProject(route.params.projectId as string)
})

onUnmounted(() => {
  editor.reset()
})
</script>

<template>
  <div class="flex h-full">
    <!-- Sidebar chapitres / scènes -->
    <aside class="w-56 shrink-0 border-r border-gray-200 dark:border-gray-700
                  bg-gray-50 dark:bg-gray-900 overflow-y-auto">
      <div class="p-3">
        <p class="text-xs font-medium text-gray-400 uppercase tracking-wide mb-2">Chapitres</p>
        <div v-for="chapter in editor.chapters" :key="chapter.id" class="mb-3">
          <p class="text-sm font-medium text-gray-700 dark:text-gray-300 px-2 py-1">
            {{ chapter.title }}
          </p>
          <button
            v-for="scene in chapter.scenes"
            :key="scene.id"
            class="w-full text-left text-xs px-3 py-1.5 rounded-md transition-colors truncate"
            :class="editor.activeScene?.id === scene.id
              ? 'bg-brand-50 dark:bg-brand-900/20 text-brand-600 dark:text-brand-400'
              : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800'"
            @click="editor.setActiveScene(scene)"
          >
            {{ scene.title }}
          </button>
        </div>
      </div>
    </aside>

    <!-- Zone d'édition -->
    <div class="flex-1 flex flex-col overflow-hidden">
      <div v-if="!editor.activeScene" class="flex-1 flex items-center justify-center">
        <p class="text-sm text-gray-400">Sélectionne une scène pour commencer.</p>
      </div>

      <template v-else>
        <!-- Barre outils scène -->
        <div class="flex items-center gap-3 px-4 h-10 border-b border-gray-200 dark:border-gray-700 shrink-0">
          <span class="text-sm font-medium text-gray-700 dark:text-gray-300 truncate">
            {{ editor.activeScene.title }}
          </span>
          <span class="ml-auto text-xs text-gray-400">
            {{ editor.saving ? 'Enregistrement…' : `${editor.activeScene.word_count} mots` }}
          </span>
        </div>

        <!-- Éditeur -->
        <div class="flex-1 overflow-y-auto p-6">
          <textarea
            :value="editor.activeScene.content ?? ''"
            class="w-full h-full min-h-96 resize-none text-gray-900 dark:text-gray-100
                   bg-transparent text-base leading-relaxed focus:outline-none"
            placeholder="Commence à écrire…"
            @input="editor.onContentChange(($event.target as HTMLTextAreaElement).value)"
          />
        </div>
      </template>
    </div>
  </div>
</template>
