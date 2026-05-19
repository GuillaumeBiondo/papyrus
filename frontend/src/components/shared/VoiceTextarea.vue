<script setup lang="ts">
import VoiceRecorder from '@/components/shared/VoiceRecorder.vue'

const props = withDefaults(defineProps<{
  modelValue: string
  placeholder?: string
  rows?: number
  source?: string
  textareaClass?: string
}>(), {
  placeholder: '',
  rows: 4,
  source: 'notebook',
  textareaClass: '',
})

const emit = defineEmits<{
  'update:modelValue': [value: string]
}>()

function onInput(e: Event) {
  emit('update:modelValue', (e.target as HTMLTextAreaElement).value)
}

function onChunk(text: string) {
  const sep = props.modelValue && !props.modelValue.endsWith(' ') ? ' ' : ''
  emit('update:modelValue', props.modelValue + sep + text)
}
</script>

<template>
  <div class="relative">
    <textarea
      :value="modelValue"
      :placeholder="placeholder"
      :rows="rows"
      class="w-full text-sm border border-gray-300 dark:border-gray-700 rounded-md p-3 pb-9
             bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100
             focus:outline-none focus:ring-2 focus:ring-brand-400 resize-none"
      :class="textareaClass"
      @input="onInput"
    />
    <div class="absolute bottom-2 right-2">
      <VoiceRecorder :source="source" @chunk="onChunk" />
    </div>
  </div>
</template>
