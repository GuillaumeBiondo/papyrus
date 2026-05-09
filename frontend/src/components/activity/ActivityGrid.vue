<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import type { ActivityDay } from '@/types'

const props = defineProps<{ days: ActivityDay[] }>()

const scrollEl = ref<HTMLElement | null>(null)

onMounted(() => {
  if (scrollEl.value) scrollEl.value.scrollLeft = scrollEl.value.scrollWidth
})

const maxWords = computed(() => Math.max(1, ...props.days.map(d => d.words)))

// Découpe les jours en semaines (colonnes)
const weeks = computed(() => {
  if (!props.days.length) return []
  const first     = new Date(props.days[0].date)
  const startPad  = first.getDay() // 0=dim → on décale pour commencer lundi
  const padded    = Array.from({ length: (startPad + 6) % 7 }, () => null)
  const all       = [...padded, ...props.days]
  const result: (ActivityDay | null)[][] = []
  for (let i = 0; i < all.length; i += 7) result.push(all.slice(i, i + 7) as (ActivityDay | null)[])
  return result
})

const DAY_LABELS = ['L', 'M', 'M', 'J', 'V', 'S', 'D']

// Couleur d'une cellule
function cellStyle(day: ActivityDay | null) {
  if (!day) return {}
  const hasLogin  = day.logins > 0
  const hasWords  = day.words > 0
  if (!hasLogin && !hasWords) return {}

  if (hasWords) {
    const intensity = Math.min(1, day.words / (maxWords.value * 0.7))
    const l = Math.round(85 - intensity * 45)  // 85% → 40%
    return { backgroundColor: `hsl(220, 80%, ${l}%)` }  // bleu
  }
  // Login uniquement (pas de mots) → vert pâle
  return { backgroundColor: '#bbf7d0' }  // green-200
}

function tooltip(day: ActivityDay | null) {
  if (!day) return ''
  const parts: string[] = [day.date]
  if (day.words > 0)  parts.push(`${day.words} mots`)
  if (day.logins > 0) parts.push(`${day.logins} connexion${day.logins > 1 ? 's' : ''}`)
  return parts.join(' · ')
}

// Mois pour les labels sur l'axe des x
const monthLabels = computed(() => {
  const labels: { label: string; col: number }[] = []
  let lastMonth = -1
  weeks.value.forEach((week, col) => {
    const day = week.find(d => d !== null)
    if (!day) return
    const m = new Date(day.date).getMonth()
    if (m !== lastMonth) {
      labels.push({
        col,
        label: new Date(day.date).toLocaleDateString('fr-FR', { month: 'short' }),
      })
      lastMonth = m
    }
  })
  return labels
})
</script>

<template>
  <div ref="scrollEl" class="overflow-x-auto">
    <!-- Labels mois -->
    <div class="flex gap-[3px] mb-1 ml-6">
      <template v-for="(month, i) in monthLabels" :key="i">
        <div
          class="text-[10px] text-gray-400"
          :style="{ width: `${(month.col === 0 ? 0 : 1) * 15}px`, minWidth: '0' }"
        />
        <span class="text-[10px] text-gray-400 whitespace-nowrap">{{ month.label }}</span>
      </template>
    </div>

    <div class="flex gap-1">
      <!-- Labels jours -->
      <div class="flex flex-col gap-[3px] mr-1 mt-[3px]">
        <span v-for="d in DAY_LABELS" :key="d" class="text-[10px] text-gray-400 h-[11px] flex items-center">
          {{ d }}
        </span>
      </div>

      <!-- Grille -->
      <div class="flex gap-[3px]">
        <div
          v-for="(week, wi) in weeks"
          :key="wi"
          class="flex flex-col gap-[3px]"
        >
          <div
            v-for="(day, di) in week"
            :key="di"
            class="w-[11px] h-[11px] rounded-[2px] transition-colors"
            :class="day ? '' : 'invisible'"
            :style="day ? { backgroundColor: '#e5e7eb', ...cellStyle(day) } : {}"
            :title="tooltip(day)"
          />
        </div>
      </div>
    </div>

    <!-- Légende -->
    <div class="flex items-center gap-4 mt-3 ml-6">
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
