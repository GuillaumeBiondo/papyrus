<script setup lang="ts">
import VoiceRecorder from '@/components/shared/VoiceRecorder.vue'

const props = withDefaults(defineProps<{
  modelValue: string
  placeholder?: string
  source?: string
  inputClass?: string
}>(), {
  placeholder: '',
  source: 'notebook',
  inputClass: '',
})

const emit = defineEmits<{
  'update:modelValue': [value: string]
  'submit': []
}>()

function onInput(e: Event) {
  emit('update:modelValue', (e.target as HTMLInputElement).value)
}

function onChunk(text: string) {
  const sep = props.modelValue && !props.modelValue.endsWith(' ') ? ' ' : ''
  emit('update:modelValue', props.modelValue + sep + text)
}
</script>

<template>
  <div class="relative">
    <input
      type="text"
      :value="modelValue"
      :placeholder="placeholder"
      class="w-full text-sm border border-gray-200 dark:border-gray-700 rounded-lg
             bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-gray-100
             px-3 py-1.5 pr-8 focus:outline-none focus:ring-1 focus:ring-brand-500"
      :class="inputClass"
      @input="onInput"
      @keyup.enter="emit('submit')"
    />
    <div class="absolute inset-y-0 right-1.5 flex items-center">
      <VoiceRecorder :source="source" @chunk="onChunk" />
    </div>
  </div>
</template>
