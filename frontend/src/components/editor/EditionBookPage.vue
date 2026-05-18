<script setup lang="ts">
import type { BookPage, PaginationMetrics } from '@/composables/useEditionPaginator'
import { computed } from 'vue'

const props = defineProps<{
  page: BookPage
  metrics: PaginationMetrics
  isThumb?: boolean
}>()

const HEADER_LABELS: Record<string, string> = {
  book_title:    '',
  chapter_title: '',
  arc_title:     '',
  author_name:   '',
  page_number:   '',
  none:          '',
}

const pageStyle = computed(() => ({
  width:  `${props.metrics.pageWidthPx}px`,
  height: `${props.metrics.pageHeightPx}px`,
  paddingTop:    `${props.metrics.marginTopPx}px`,
  paddingBottom: `${props.metrics.marginBottomPx}px`,
  // Inner/outer depend on verso/recto
  paddingLeft:  props.page.isVerso
    ? `${props.metrics.marginOuterPx}px`
    : `${props.metrics.marginInnerPx}px`,
  paddingRight: props.page.isVerso
    ? `${props.metrics.marginInnerPx}px`
    : `${props.metrics.marginOuterPx}px`,
  fontSize:   `${props.metrics.fontSizePx}px`,
  lineHeight: String(props.metrics.lineHeightPx / props.metrics.fontSizePx),
}))

const contentStyle = computed(() => ({
  height: `${props.metrics.snappedContentHeightPx}px`,
  textAlign: props.metrics.textAlign,
}))

const footerNum = computed(() => props.page.showFooter ? props.page.displayNum : '')
</script>

<template>
  <div
    class="book-page relative bg-white shadow-md flex flex-col overflow-hidden select-none"
    :style="pageStyle"
    :class="isThumb ? 'pointer-events-none' : ''"
  >
    <!-- ── En-tête ── -->
    <div
      class="shrink-0 flex items-end overflow-hidden"
      :class="metrics.headerRule && page.showHeader ? 'border-b border-gray-200' : ''"
      :style="{ height: `${metrics.headerHeightPx}px`, paddingBottom: `${metrics.ruleSpaceBeforePx}px` }"
    >
      <template v-if="page.showHeader">
        <span v-if="page.isVerso" class="text-left text-gray-500 truncate" style="font-size: 0.7em">{{ page.headerLeft }}</span>
        <span v-else              class="ml-auto text-right text-gray-500 truncate" style="font-size: 0.7em">{{ page.headerRight }}</span>
      </template>
    </div>

    <!-- ── Contenu ── -->
    <div
      class="flex-1 overflow-hidden relative"
      :style="{ ...contentStyle, paddingTop: `${metrics.ruleSpaceAfterPx}px`, paddingBottom: `${metrics.ruleSpaceBeforePx}px` }"
    >

      <!-- Page blanche -->
      <template v-if="page.kind === 'blank'" />

      <!-- Page de titre -->
      <template v-else-if="page.kind === 'auto_title'">
        <div class="h-full flex flex-col items-center justify-center text-center gap-3">
          <p class="font-bold leading-tight" style="font-size: 1.8em">{{ page.bigTitle }}</p>
          <p class="text-gray-500" style="font-size: 0.85em">{{ page.subTitle }}</p>
        </div>
      </template>

      <!-- Page de copyright -->
      <template v-else-if="page.kind === 'auto_copyright'">
        <div class="h-full flex flex-col justify-end gap-1 pb-2">
          <div v-for="(block, i) in page.blocks" :key="i"
            class="text-gray-500 leading-snug"
            style="font-size: 0.72em"
            v-html="block"
          />
        </div>
      </template>

      <!-- Table des matières -->
      <template v-else-if="page.kind === 'auto_toc'">
        <div class="pt-6">
          <p class="font-semibold mb-4" style="font-size: 1.1em">{{ page.bigTitle }}</p>
          <div v-for="(entry, i) in page.blocks" :key="i" v-html="entry" class="toc-entry" />
        </div>
      </template>

      <!-- Liminaire (dédicace, épigraphe…) -->
      <template v-else-if="page.kind === 'liminary'">
        <div class="h-full flex flex-col justify-center gap-3">
          <p class="text-gray-400 italic mb-2" style="font-size: 0.75em">{{ page.docLabel }}</p>
          <div
            v-for="(block, i) in page.blocks" :key="i"
            v-html="block"
            class="prose-edition"
          />
        </div>
      </template>

      <!-- Page de partie (arc) -->
      <template v-else-if="page.kind === 'part'">
        <div class="h-full flex flex-col items-center justify-center text-center">
          <p class="font-bold" style="font-size: 1.4em">{{ page.bigTitle }}</p>
        </div>
      </template>

      <!-- Corps du roman (body) — premier ou suite -->
      <template v-else-if="page.kind === 'body'">
        <!-- Titre du chapitre sur la première page -->
        <div
          v-if="page.bigTitle"
          class="text-center"
          :style="{ marginBottom: `${metrics.chapterTitleSpaceAfterEm}em` }"
        >
          <p class="font-semibold" :style="{ fontSize: '1.2em', paddingTop: `${metrics.chapterTitleSpaceBeforeEm}em` }">{{ page.bigTitle }}</p>
        </div>
        <!-- Blocs de contenu -->
        <div
          v-for="(block, i) in page.blocks"
          :key="i"
          v-html="block"
          class="prose-edition"
        />
      </template>

      <!-- Annexe -->
      <template v-else-if="page.kind === 'annex'">
        <div class="mb-4">
          <p class="font-semibold" style="font-size: 1.1em; padding-top: 1em">{{ page.bigTitle }}</p>
        </div>
        <div
          v-for="(block, i) in page.blocks"
          :key="i"
          v-html="block"
          class="prose-edition"
        />
      </template>
    </div>

    <!-- ── Pied de page ── -->
    <div
      class="shrink-0 flex items-start"
      :class="metrics.footerRule && page.showFooter ? 'border-t border-gray-200' : ''"
      :style="{ height: `${metrics.footerHeightPx}px`, paddingTop: `${metrics.ruleSpaceAfterPx}px` }"
    >
      <span v-if="footerNum" class="w-full text-center text-gray-500" style="font-size: 0.7em">
        {{ footerNum }}
      </span>
    </div>
  </div>
</template>

<style scoped>
.prose-edition :deep(p)           { margin-bottom: 0.6em; }
.prose-edition :deep(blockquote)  { padding-left: 1em; border-left: 2px solid #d1d5db; color: #6b7280; font-style: italic; margin: 0.8em 0; }
.prose-edition :deep(em)          { font-style: italic; }
.prose-edition :deep(strong)      { font-weight: 600; }
.prose-edition :deep(.scene-sep)  { text-align: center; color: #9ca3af; letter-spacing: 0.4em; }
.prose-edition :deep(hr)          { border: none; border-top: 1px solid #d1d5db; margin: 1em 0; }

.toc-entry :deep(.toc-part)    { font-weight: 600; margin-top: 0.8em; font-size: 0.9em; }
.toc-entry :deep(.toc-chapter) { display: flex; align-items: baseline; gap: 0; padding-left: 1.2em; font-size: 0.85em; color: #4b5563; margin-bottom: 0.15em; }
.toc-entry :deep(.toc-clabel)  { flex-shrink: 0; }
.toc-entry :deep(.toc-leader)  { flex: 1; min-width: 0.5em; border-bottom: 1px dotted #9ca3af; margin: 0 0.35em 0.18em; }
.toc-entry :deep(.toc-pnum)    { flex-shrink: 0; color: #6b7280; }
</style>
