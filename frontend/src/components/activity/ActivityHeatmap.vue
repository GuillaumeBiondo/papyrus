<script setup lang="ts">
import { computed } from 'vue'
import type { ActivityHour } from '@/types'

const props = defineProps<{ hours: ActivityHour[] }>()

// Jours de la semaine (0=dim dans l'API, on réordonne lundi en premier)
const DAY_LABELS = ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim']
const DAY_ORDER  = [1, 2, 3, 4, 5, 6, 0]  // API: 0=dim, 1=lun…

const HOUR_LABELS = Array.from({ length: 24 }, (_, i) => `${i}h`)

const maxWords = computed(() => Math.max(1, ...props.hours.map(h => h.words)))

function getCell(day: number, hour: number): ActivityHour | undefined {
  // day est l'index dans DAY_ORDER (0=lun … 6=dim)
  return props.hours.find(h => h.day === DAY_ORDER[day] && h.hour === hour)
}

function cellStyle(day: number, hour: number) {
  const cell = getCell(day, hour)
  if (!cell) return {}
  if (cell.words > 0) {
    const intensity = Math.min(1, cell.words / (maxWords.value * 0.7))
    const l = Math.round(85 - intensity * 45)
    return { backgroundColor: `hsl(220, 80%, ${l}%)` }
  }
  if (cell.logins > 0) return { backgroundColor: '#bbf7d0' }
  return {}
}

function tooltip(day: number, hour: number) {
  const cell = getCell(day, hour)
  if (!cell || (!cell.words && !cell.logins)) return `${DAY_LABELS[day]} ${hour}h`
  const parts = [`${DAY_LABELS[day]} ${hour}h`]
  if (cell.words > 0)  parts.push(`${cell.words} mots`)
  if (cell.logins > 0) parts.push(`${cell.logins} session${cell.logins > 1 ? 's' : ''}`)
  return parts.join(' · ')
}
</script>

<template>
  <div class="overflow-x-auto">
    <div class="flex gap-1">
      <!-- Labels jours -->
      <div class="flex flex-col gap-[3px] mr-1 pt-4">
        <span v-for="d in DAY_LABELS" :key="d" class="text-[10px] text-gray-400 h-[14px] flex items-center w-8">
          {{ d }}
        </span>
      </div>

      <div class="flex-1 min-w-0">
        <!-- Labels heures -->
        <div class="flex gap-[3px] mb-1">
          <div
            v-for="(label, h) in HOUR_LABELS"
            :key="h"
            class="flex-1 text-center"
          >
            <span
              v-if="h % 3 === 0"
              class="text-[10px] text-gray-400"
            >{{ label }}</span>
          </div>
        </div>

        <!-- Grille -->
        <div class="flex flex-col gap-[3px]">
          <div v-for="(_, day) in DAY_LABELS" :key="day" class="flex gap-[3px]">
            <div
              v-for="hour in 24"
              :key="hour"
              class="flex-1 h-[14px] rounded-[2px] transition-colors cursor-default"
              :style="{ backgroundColor: '#e5e7eb', ...cellStyle(day, hour - 1) }"
              :title="tooltip(day, hour - 1)"
            />
          </div>
        </div>
      </div>
    </div>

    <!-- Légende -->
    <div class="flex items-center gap-4 mt-3 ml-10">
      <div class="flex items-center gap-1.5">
        <div class="w-3 h-3 rounded-[2px] bg-[#bbf7d0]" />
        <span class="text-[10px] text-gray-400">Connexion</span>
      </div>
      <div class="flex items-center gap-1.5">
        <div class="flex gap-0.5">
          <div v-for="l in [65, 50, 35]" :key="l" class="w-3 h-3 rounded-[2px]" :style="{ backgroundColor: `hsl(220,80%,${l}%)` }" />
        </div>
        <span class="text-[10px] text-gray-400">Mots écrits</span>
      </div>
    </div>
  </div>
</template>
