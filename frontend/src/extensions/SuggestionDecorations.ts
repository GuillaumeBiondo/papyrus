import { Extension } from '@tiptap/core'
import type { Node as PmNode } from '@tiptap/pm/model'
import { PluginKey } from '@tiptap/pm/state'
import { Plugin } from '@tiptap/pm/state'
import { Decoration, DecorationSet } from '@tiptap/pm/view'
import type { SuggestionBatch } from '@/types/suggestion.types'

export const suggestionPluginKey = new PluginKey<DecorationSet>('suggestionDecorations')

function buildDecos(doc: PmNode, batches: SuggestionBatch[]): DecorationSet {
  const decos: Decoration[] = []
  const docSize = doc.content.size

  for (const batch of batches) {
    if (batch.status !== 'pending') continue

    for (const change of batch.changes) {
      const from = Math.max(0, Math.min(change.from, docSize))
      const to = Math.max(from, Math.min(change.to, docSize))

      if (to > from) {
        decos.push(
          Decoration.inline(from, to, {
            class: 'suggestion-delete',
            'data-suggestion-batch': batch.id,
          }),
        )
      }

      if (change.suggestedText) {
        const el = document.createElement('span')
        el.className = 'suggestion-insert'
        el.textContent = change.suggestedText
        decos.push(
          Decoration.widget(to, el, {
            side: 1,
            key: `suggestion-insert-${batch.id}-${change.from}`,
          }),
        )
      }
    }
  }

  return DecorationSet.create(doc, decos)
}

export function createSuggestionExtension(getBatches: () => SuggestionBatch[]) {
  return Extension.create({
    name: 'suggestionDecorations',

    addProseMirrorPlugins() {
      return [
        new Plugin({
          key: suggestionPluginKey,
          state: {
            init(_, { doc }) {
              return buildDecos(doc, getBatches())
            },
            apply(tr, oldSet) {
              if (tr.docChanged || tr.getMeta(suggestionPluginKey)) {
                return buildDecos(tr.doc, getBatches())
              }
              return oldSet.map(tr.mapping, tr.doc)
            },
          },
          props: {
            decorations(state) {
              return suggestionPluginKey.getState(state)
            },
          },
        }),
      ]
    },
  })
}
