<script setup lang="ts">
import { computed } from 'vue'
import { marked } from 'marked'

const props = defineProps<{ content: string }>()

const html = computed(() => marked(props.content || '', { breaks: true }) as string)
</script>

<template>
  <div class="markdown-body prose prose-sm dark:prose-invert max-w-none" v-html="html" />
</template>

<style>
.markdown-body h1 { font-size: 1.25rem; font-weight: 700; margin: 0.75em 0 0.4em; }
.markdown-body h2 { font-size: 1.1rem; font-weight: 600; margin: 0.75em 0 0.4em; }
.markdown-body h3 { font-size: 1rem; font-weight: 600; margin: 0.5em 0 0.3em; }
.markdown-body p  { margin: 0 0 0.6em; }
.markdown-body ul, .markdown-body ol { padding-left: 1.4em; margin: 0 0 0.6em; }
.markdown-body li { margin-bottom: 0.2em; }
.markdown-body strong { font-weight: 600; }
.markdown-body em { font-style: italic; }
.markdown-body code { font-family: monospace; font-size: 0.85em; background: rgba(0,0,0,.06); border-radius: 3px; padding: 0.1em 0.3em; }
.dark .markdown-body code { background: rgba(255,255,255,.08); }
.markdown-body pre { background: rgba(0,0,0,.06); border-radius: 6px; padding: 0.75em 1em; overflow-x: auto; margin: 0 0 0.6em; }
.dark .markdown-body pre { background: rgba(255,255,255,.06); }
.markdown-body hr { border: none; border-top: 1px solid #e5e7eb; margin: 1em 0; }
.dark .markdown-body hr { border-color: #374151; }
.markdown-body blockquote { border-left: 3px solid #d1d5db; padding-left: 0.75em; color: #6b7280; margin: 0 0 0.6em; }
.dark .markdown-body blockquote { border-color: #4b5563; color: #9ca3af; }
.markdown-body a { color: #7c3aed; text-decoration: underline; }
.dark .markdown-body a { color: #a78bfa; }
</style>
