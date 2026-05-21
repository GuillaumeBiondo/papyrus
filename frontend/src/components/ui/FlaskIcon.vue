<script setup lang="ts">
import { computed } from 'vue'

const props = defineProps<{ score: number; color: string }>()

const uid = Math.random().toString(36).slice(2, 7)

const level = computed(() => {
  if (props.score >= 0.9) return 'calm'
  if (props.score >= 0.7) return 'gentle'
  if (props.score >= 0.45) return 'active'
  return 'chaotic'
})

interface Bubble { cx: number; cy: number; r: number; dur: number; delay: number }

const BUBBLES: Record<string, Bubble[]> = {
  calm: [
    { cx: 16, cy: 36, r: 2,   dur: 3.5,  delay: 0 },
  ],
  gentle: [
    { cx: 12, cy: 37, r: 1.8, dur: 2.3,  delay: 0 },
    { cx: 21, cy: 35, r: 1.5, dur: 2.5,  delay: 1.15 },
  ],
  active: [
    { cx: 10, cy: 37, r: 2,   dur: 1.4,  delay: 0 },
    { cx: 17, cy: 35, r: 1.5, dur: 1.3,  delay: 0.47 },
    { cx: 23, cy: 37, r: 2,   dur: 1.5,  delay: 0.92 },
  ],
  chaotic: [
    { cx: 9,  cy: 37, r: 2.2, dur: 0.75, delay: 0 },
    { cx: 15, cy: 36, r: 1.5, dur: 0.70, delay: 0.15 },
    { cx: 22, cy: 37, r: 2,   dur: 0.80, delay: 0.30 },
    { cx: 12, cy: 38, r: 1.2, dur: 0.65, delay: 0.50 },
    { cx: 20, cy: 36, r: 1.8, dur: 0.72, delay: 0.62 },
  ],
}

const SPARKS = [
  { cx: 14, cy: 19, r: 1.3, delay: 0 },
  { cx: 19, cy: 17, r: 1.0, delay: 0.22 },
  { cx: 12, cy: 17, r: 0.9, delay: 0.44 },
]

const bubbles = computed(() => BUBBLES[level.value])
</script>

<template>
  <div class="inline-block" :class="level === 'chaotic' ? 'flask-shaking' : ''">
    <svg viewBox="0 0 32 44" class="w-9 h-11" fill="none" xmlns="http://www.w3.org/2000/svg">
      <defs>
        <clipPath :id="`fc-${uid}`">
          <path d="M11,2 L11,14 C7,16 3,20 3,28 Q3,42 16,42 Q29,42 29,28 C29,20 25,16 21,14 L21,2 Z"/>
        </clipPath>
      </defs>

      <!-- Liquid fill -->
      <rect x="0" y="24" width="32" height="20"
        :clip-path="`url(#fc-${uid})`" :fill="color" opacity="0.22"/>

      <!-- Bubbles (clipped to flask) -->
      <g :clip-path="`url(#fc-${uid})`">
        <circle
          v-for="(b, i) in bubbles" :key="i"
          :cx="b.cx" :cy="b.cy" :r="b.r" :fill="color"
          class="bubble"
          :style="`--dur:${b.dur}s;--delay:${b.delay}s`"
        />
      </g>

      <!-- Flask glass outline (drawn on top of liquid) -->
      <path
        d="M11,2 L11,14 C7,16 3,20 3,28 Q3,42 16,42 Q29,42 29,28 C29,20 25,16 21,14 L21,2 Z"
        stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"
        class="text-gray-400 dark:text-gray-600"
      />
      <!-- Rim -->
      <line x1="10" y1="2" x2="22" y2="2"
        stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
        class="text-gray-400 dark:text-gray-600"
      />
      <!-- Glass shine -->
      <path d="M6,27 Q5,34 6,40" stroke="white" stroke-width="1.3" stroke-linecap="round" opacity="0.35"/>

      <!-- Sparks in the vapor zone (chaotic only) -->
      <g v-if="level === 'chaotic'">
        <circle
          v-for="(s, i) in SPARKS" :key="i"
          :cx="s.cx" :cy="s.cy" :r="s.r" :fill="color"
          class="spark"
          :style="`--spark-delay:${s.delay}s`"
        />
      </g>
    </svg>
  </div>
</template>

<style scoped>
/* ── Bubbles ── */
.bubble {
  animation: bubble-rise var(--dur, 2s) var(--delay, 0s) ease-in infinite;
  transform-box: fill-box;
  transform-origin: center;
}
@keyframes bubble-rise {
  0%   { transform: translateY(0);     opacity: 0.75; }
  75%  {                               opacity: 0.35; }
  100% { transform: translateY(-17px); opacity: 0; }
}

/* ── Sparks ── */
.spark {
  animation: spark-pop 0.55s var(--spark-delay, 0s) ease-out infinite;
  transform-box: fill-box;
  transform-origin: center;
}
@keyframes spark-pop {
  0%   { transform: scale(0);   opacity: 0; }
  30%  { transform: scale(1.4); opacity: 1; }
  70%  { transform: scale(0.8); opacity: 0.4; }
  100% { transform: scale(0);   opacity: 0; }
}

/* ── Flask shake (chaotic) ── */
.flask-shaking {
  animation: flask-shake 0.38s ease-in-out infinite;
  transform-origin: bottom center;
}
@keyframes flask-shake {
  0%, 100% { transform: rotate(0deg) translateY(0); }
  25%      { transform: rotate(-2.5deg) translateY(-0.5px); }
  75%      { transform: rotate(2.5deg)  translateY(-0.5px); }
}
</style>
