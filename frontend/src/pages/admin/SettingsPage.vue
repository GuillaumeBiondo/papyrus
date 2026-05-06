<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { adminService } from '@/services/admin.service'
import type { Setting } from '@/types'

const settings = ref<Setting[]>([])
const loading = ref(true)
const saving = ref<Record<string, boolean>>({})
const saved = ref<Record<string, boolean>>({})

onMounted(async () => {
  try {
    const data = await adminService.getSettings()
    settings.value = data.settings
  } finally {
    loading.value = false
  }
})

function grouped() {
  return settings.value.reduce<Record<string, Setting[]>>((acc, s) => {
    ;(acc[s.group] ??= []).push(s)
    return acc
  }, {})
}

function displayValue(s: Setting) {
  if (typeof s.value === 'boolean') return s.value
  if (s.value === null) return ''
  return String(s.value)
}

function isBoolean(s: Setting) {
  return typeof s.value === 'boolean'
}

async function save(s: Setting, value: unknown) {
  saving.value[s.key] = true
  try {
    const { setting } = await adminService.updateSetting(s.key, value)
    const idx = settings.value.findIndex(x => x.key === s.key)
    if (idx !== -1) settings.value[idx] = setting
    saved.value[s.key] = true
    setTimeout(() => delete saved.value[s.key], 2000)
  } finally {
    delete saving.value[s.key]
  }
}

function onToggle(s: Setting) {
  save(s, !s.value)
}

function onInput(s: Setting, e: Event) {
  const raw = (e.target as HTMLInputElement).value
  const val = raw === '' ? null : (isNaN(Number(raw)) ? raw : Number(raw))
  save(s, val)
}
</script>

<template>
  <div class="p-8 max-w-2xl">
    <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-6">Paramètres</h1>

    <div v-if="loading" class="text-gray-400 text-sm">Chargement…</div>

    <template v-else>
      <div
        v-for="(items, group) in grouped()"
        :key="group"
        class="mb-6 bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-800 overflow-hidden"
      >
        <div class="px-4 py-2 border-b border-gray-100 dark:border-gray-800">
          <span class="text-xs font-semibold text-gray-500 dark:text-gray-500 uppercase tracking-wider">{{ group }}</span>
        </div>
        <div class="divide-y divide-gray-50 dark:divide-gray-800/50">
          <div v-for="s in items" :key="s.key" class="flex items-center justify-between px-4 py-3 gap-4">
            <div>
              <p class="text-sm text-gray-800 dark:text-gray-200">{{ s.label ?? s.key }}</p>
              <p class="text-xs text-gray-400 font-mono">{{ s.key }}</p>
            </div>
            <div class="flex items-center gap-2 shrink-0">
              <!-- Boolean toggle -->
              <button
                v-if="isBoolean(s)"
                class="relative inline-flex h-5 w-9 rounded-full transition-colors"
                :class="s.value ? 'bg-brand-600' : 'bg-gray-300 dark:bg-gray-700'"
                @click="onToggle(s)"
              >
                <span
                  class="absolute top-0.5 left-0.5 h-4 w-4 rounded-full bg-white shadow transition-transform"
                  :class="s.value ? 'translate-x-4' : 'translate-x-0'"
                />
              </button>
              <!-- Text/number input -->
              <input
                v-else
                class="w-32 rounded-md border border-gray-300 dark:border-gray-700
                       bg-white dark:bg-gray-800 px-2 py-1 text-sm
                       text-gray-900 dark:text-gray-100 outline-none
                       focus:ring-2 focus:ring-brand-500"
                :value="displayValue(s)"
                placeholder="null"
                @change="onInput(s, $event)"
              />
              <span v-if="saving[s.key]" class="text-xs text-gray-400">…</span>
              <span v-else-if="saved[s.key]" class="text-xs text-green-600 dark:text-green-400">✓</span>
            </div>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>
