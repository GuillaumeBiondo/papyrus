<script setup lang="ts">
defineProps<{ size?: 'xs' | 'sm' | 'md' }>()
</script>

<template>
  <svg
    class="shrink-0 text-amber-500"
    :class="size === 'xs' ? 'w-3 h-3' : size === 'md' ? 'w-5 h-5' : 'w-3.5 h-3.5'"
    fill="none" stroke="currentColor" viewBox="0 0 24 24"
    aria-label="Fonctionnalité premium"
  >
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
      d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
  </svg>
</template>
