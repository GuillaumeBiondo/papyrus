<script setup lang="ts">
import { ref } from 'vue'
import { useCardsStore } from '@/stores/cards.store'
import type { CardImage } from '@/types'

const cards = useCardsStore()

const ACCEPTED_TYPES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp']
const MAX_SIZE_BYTES = 5 * 1024 * 1024

const uploading = ref(false)
const dragOver  = ref(false)
const error     = ref<string | null>(null)

function validateFile(file: File): string | null {
  if (!ACCEPTED_TYPES.includes(file.type)) {
    return 'Format non supporté. Utilisez JPG, PNG, GIF ou WebP.'
  }
  if (file.size > MAX_SIZE_BYTES) {
    return 'Fichier trop volumineux (max 5 Mo).'
  }
  return null
}

async function handleFiles(files: FileList | null) {
  if (!files?.length) return
  error.value = null

  const file = files[0]
  const msg  = validateFile(file)
  if (msg) { error.value = msg; return }

  uploading.value = true
  try {
    await cards.uploadImage(file)
  } catch {
    error.value = 'Erreur lors de l\'upload. Réessayez.'
  } finally {
    uploading.value = false
  }
}

function onFileInput(e: Event) {
  handleFiles((e.target as HTMLInputElement).files)
  ;(e.target as HTMLInputElement).value = ''
}

function onDrop(e: DragEvent) {
  dragOver.value = false
  handleFiles(e.dataTransfer?.files ?? null)
}

async function onDelete(image: CardImage) {
  error.value = null
  try {
    await cards.removeImage(image.id)
  } catch {
    error.value = 'Erreur lors de la suppression.'
  }
}

async function onSetAvatar(image: CardImage) {
  error.value = null
  try {
    await cards.setAvatarImage(image.id)
  } catch {
    error.value = 'Erreur lors de la mise à jour.'
  }
}

function formatSize(bytes: number): string {
  if (bytes < 1024) return bytes + ' o'
  if (bytes < 1024 * 1024) return Math.round(bytes / 1024) + ' Ko'
  return (bytes / (1024 * 1024)).toFixed(1) + ' Mo'
}
</script>

<template>
  <div class="p-6 space-y-5">

    <!-- Zone de dépôt -->
    <label
      class="flex flex-col items-center justify-center gap-2 w-full rounded-xl border-2 border-dashed
             cursor-pointer transition-colors select-none"
      :class="dragOver
        ? 'border-brand-400 bg-brand-50 dark:bg-brand-900/20'
        : 'border-gray-300 dark:border-gray-600 hover:border-brand-400 hover:bg-gray-50 dark:hover:bg-gray-800/50'"
      @dragover.prevent="dragOver = true"
      @dragleave="dragOver = false"
      @drop.prevent="onDrop"
    >
      <input type="file" accept="image/jpeg,image/png,image/gif,image/webp" class="sr-only" @change="onFileInput" />

      <div v-if="uploading" class="py-8 flex flex-col items-center gap-2">
        <svg class="w-6 h-6 animate-spin text-brand-500" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
        </svg>
        <span class="text-sm text-gray-400">Upload en cours…</span>
      </div>

      <div v-else class="py-8 flex flex-col items-center gap-1">
        <svg class="w-8 h-8 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14
                   M8 10a2 2 0 100-4 2 2 0 000 4zm12 6H4a2 2 0 01-2-2V6a2 2 0 012-2h16a2 2 0 012 2v8a2 2 0 01-2 2z"/>
        </svg>
        <span class="text-sm font-medium text-gray-500 dark:text-gray-400">
          Cliquez ou glissez une image
        </span>
        <span class="text-xs text-gray-400">JPG, PNG, GIF, WebP · max 5 Mo</span>
      </div>
    </label>

    <!-- Message d'erreur -->
    <p v-if="error" class="text-sm text-red-500 dark:text-red-400">{{ error }}</p>

    <!-- Grille d'images -->
    <div v-if="cards.activeCard?.images?.length" class="grid grid-cols-2 gap-3 sm:grid-cols-3">
      <div
        v-for="image in cards.activeCard.images"
        :key="image.id"
        class="group relative rounded-xl overflow-hidden border bg-gray-100 dark:bg-gray-800 aspect-square"
        :class="image.is_avatar
          ? 'border-brand-400 dark:border-brand-500 ring-2 ring-brand-300 dark:ring-brand-700'
          : 'border-gray-200 dark:border-gray-700'"
      >
        <img
          :src="image.url"
          :alt="image.original_name"
          class="w-full h-full object-cover"
          loading="lazy"
        />

        <!-- Badge avatar -->
        <span
          v-if="image.is_avatar"
          class="absolute top-1.5 left-1.5 text-[10px] font-semibold px-1.5 py-0.5 rounded-full
                 bg-brand-500 text-white leading-none"
        >avatar</span>

        <!-- Overlay actions -->
        <div
          class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity
                 flex flex-col items-center justify-center gap-2"
        >
          <button
            v-if="!image.is_avatar"
            class="text-xs px-3 py-1.5 rounded-lg bg-white/90 text-gray-800 font-medium
                   hover:bg-white transition-colors"
            @click.stop="onSetAvatar(image)"
          >Définir avatar</button>

          <button
            class="text-xs px-3 py-1.5 rounded-lg bg-red-500/90 text-white font-medium
                   hover:bg-red-600 transition-colors"
            @click.stop="onDelete(image)"
          >Supprimer</button>
        </div>

        <!-- Nom + taille en bas -->
        <div
          class="absolute bottom-0 left-0 right-0 px-2 py-1 bg-black/50
                 opacity-0 group-hover:opacity-100 transition-opacity"
        >
          <p class="text-[10px] text-white truncate">{{ image.original_name }}</p>
          <p class="text-[10px] text-white/70">{{ formatSize(image.size) }}</p>
        </div>
      </div>
    </div>

    <p v-else-if="!uploading" class="text-sm text-gray-400">
      Aucune image pour cette fiche.
    </p>

  </div>
</template>
