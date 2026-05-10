# Papyrus — Suggestions de modification de texte

Système central permettant à n'importe quelle feature de proposer des
modifications (insertions, suppressions, remplacements) visibles dans
l'éditeur sous forme de diff rouge/vert, à la manière d'un `git diff`.

---

## 1. Vue d'ensemble

```
Feature (AI, grammar, style…)
    │
    │  useSuggestionService().propose(changes, options)
    ▼
suggestions.store.ts          ← état Pinia éphémère
    │
    │  watch réactif (deep)
    ▼
SuggestionDecorations.ts      ← extension TipTap / ProseMirror
    │
    ├─ Decoration.inline()    → texte barré rouge  (texte supprimé)
    └─ Decoration.widget()    → span vert inline    (texte inséré)

SuggestionPanel.vue           ← barre en bas de l'éditeur
    ├─ navigation ← →  entre les batches
    ├─ accepter / refuser un changement individuel
    └─ accepter tout / refuser tout
```

Les suggestions sont **éphémères** : elles vivent uniquement en mémoire
et sont effacées automatiquement lors du changement de scène.

---

## 2. Concepts clés

### SuggestionChange

Un changement atomique : remplacer le texte aux positions `[from, to]`
par `suggestedText`.

```typescript
interface SuggestionChange {
  from: number          // position ProseMirror dans le document
  to: number            // position ProseMirror de fin
  originalText: string  // texte actuel (pour l'affichage dans le panel)
  suggestedText: string // texte proposé (vide = suppression pure)
}
```

- `from === to` + `suggestedText` non vide → **insertion pure**
- `suggestedText` vide + `from < to` → **suppression pure**
- sinon → **remplacement**

### SuggestionBatch

Un groupe de changements provenant d'une même feature ou d'un même
appel IA. L'utilisateur peut accepter ou refuser tout le batch d'un
seul geste, ou agir changement par changement.

```typescript
interface SuggestionBatch {
  id: string
  label: string                               // affiché dans le panel
  source: string                              // identifiant technique de la feature
  changes: SuggestionChange[]
  status: 'pending' | 'accepted' | 'rejected'
  createdAt: Date
}
```

---

## 3. API publique — proposer des suggestions

Importer le composable dans n'importe quelle feature frontend :

```typescript
import { useSuggestionService } from '@/composables/useSuggestionService'

const { propose, proposeReplacement } = useSuggestionService()
```

### `proposeReplacement` — un seul changement

```typescript
proposeReplacement(
  from: number,
  to: number,
  originalText: string,
  suggestedText: string,
  options?: { label?: string; source?: string },
): SuggestionBatch
```

**Exemple — réécriture d'un passage par l'IA :**

```typescript
// Récupérer les positions du passage sélectionné via l'éditeur TipTap
const { from, to } = editor.state.selection
const originalText = editor.state.doc.textBetween(from, to)

// Appel IA (exemple fictif)
const newText = await aiService.rewrite(originalText)

proposeReplacement(from, to, originalText, newText, {
  label: 'Réécriture IA',
  source: 'ai_rewrite',
})
```

### `propose` — plusieurs changements simultanés

```typescript
propose(
  changes: SuggestionChange[],
  options?: { label?: string; source?: string },
): SuggestionBatch
```

**Exemple — correcteur de style multi-occurrences :**

```typescript
propose(
  [
    { from: 42,  to: 55,  originalText: 'il alla',    suggestedText: 'il se rendit' },
    { from: 120, to: 130, originalText: 'très beau',  suggestedText: 'magnifique'   },
    { from: 300, to: 300, originalText: '',           suggestedText: ' — '          },
  ],
  { label: 'Suggestions de style', source: 'style_check' },
)
```

**Exemple — suppression pure :**

```typescript
propose(
  [{ from: 80, to: 95, originalText: 'vraiment très ', suggestedText: '' }],
  { label: 'Suppression de doublon', source: 'redundancy_check' },
)
```

---

## 4. Obtenir les positions ProseMirror

Les positions `from` et `to` sont des offsets dans le document
ProseMirror. Quelques façons de les obtenir :

```typescript
// Depuis la sélection courante
const { from, to } = editor.state.selection

// Chercher un texte dans le document
editor.state.doc.descendants((node, pos) => {
  if (node.isText && node.text?.includes('mot')) {
    const from = pos
    const to = pos + node.nodeSize
    // …
  }
})

// Texte entre deux positions
const text = editor.state.doc.textBetween(from, to, ' ')
```

> **Attention** : les positions ProseMirror incluent les délimiteurs de
> nœuds (chaque ouverture/fermeture de paragraphe, etc. vaut 1). Ne pas
> les confondre avec des offsets de caractères dans le HTML brut.

---

## 5. Store — accès direct (lecture seule conseillé)

Pour réagir à l'état des suggestions sans les modifier :

```typescript
import { useSuggestionsStore } from '@/stores/suggestions.store'

const store = useSuggestionsStore()

store.hasPending        // boolean — true si au moins un batch en attente
store.pendingBatches    // SuggestionBatch[] — batches en attente
store.focusedBatch      // SuggestionBatch | null — batch affiché dans le panel
store.focusedBatchIndex // number — index de navigation courant
```

Actions disponibles (utilisées en interne par `SuggestionPanel`) :

| Action | Rôle |
|---|---|
| `addBatch(changes, options)` | Ajouter un batch (préférer `useSuggestionService`) |
| `markAccepted(batchId)` | Marquer accepté sans modifier le document |
| `markRejected(batchId)` | Marquer refusé sans modifier le document |
| `removeChange(batchId, idx)` | Retirer un changement individuel du batch |
| `navigatePrev()` / `navigateNext()` | Navigation ← → entre batches |
| `clearAll()` | Vider tous les batches (appelé au changement de scène) |

---

## 6. Fichiers concernés

```
frontend/src/
  types/
    suggestion.types.ts           ← interfaces SuggestionChange, SuggestionBatch
  stores/
    suggestions.store.ts          ← état Pinia
  composables/
    useSuggestionService.ts       ← API publique pour les features
  extensions/
    SuggestionDecorations.ts      ← extension TipTap + plugin ProseMirror
  components/
    editor/
      SuggestionPanel.vue         ← UI accept/reject
      SceneEditor.vue             ← intègre l'extension, watch store
  pages/
    editor/
      EditorPage.vue              ← monte SuggestionPanel, clear au changement de scène
```

---

## 7. Comportements importants

- **Effacement automatique** : `clearAll()` est appelé dès que
  `editor.activeScene.id` change. Les suggestions sont liées à la scène
  courante.
- **Application en ordre inverse** : lors d'un accept all, les
  changements sont appliqués du plus haut au plus bas offset pour ne pas
  décaler les positions des autres changements.
- **Remapping après accept individuel** : quand un seul changement est
  accepté, les offsets des changements suivants dans le même batch sont
  recalculés (`offset = suggestedText.length - (to - from)`).
- **Édition pendant les suggestions** : si l'utilisateur tape dans
  l'éditeur alors que des suggestions sont en attente, les positions
  peuvent devenir inexactes. La bonne pratique est de demander à
  l'utilisateur de traiter les suggestions avant de continuer à écrire.
- **Multi-batches** : plusieurs features peuvent proposer des batches
  simultanément. Le panel navigue entre eux avec ← →.
