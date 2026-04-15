# Papyrus — Spec technique frontend

**Vue 3 · Vite 8 · Pinia · Vue Router · Tailwind CSS 4**
Version 1.0

---

## 1. Stack et outillage

- **Vue 3** (Composition API + `<script setup>`)
- **Vite** — bundler, HMR, build PWA via `vite-plugin-pwa`
- **Vue Router 4** — routing côté client
- **Pinia** — state management
- **Tailwind CSS 4** — configuration CSS-first (`@theme`, `@variant dark`), pas de `tailwind.config.js`
- **Axios** — couche HTTP, instance configurée avec intercepteurs
- **PrimeVue** (optionnel) — composants UI si besoin de datepicker, etc.

---

## 2. Structure des dossiers

```
src/
  assets/               # fonts, images, icônes SVG
  components/
    ui/                 # composants génériques (Button, Badge, Chip, Avatar...)
    layout/             # AppTopbar, AppSidebar, AppDrawer
    editor/             # SceneEditor, AnnotationPanel, LinkedCardsBar
    cards/              # CardList, CardDetail, CardForm, OccurrenceList
    dashboard/          # ProjectCard, ProjectGrid, ProjectList
    notebook/           # NotebookDrawer, NoteCard, NoteForm
  composables/          # useDebounce, useWordCount, useTheme, useToast
  layouts/
    AppLayout.vue       # topbar + slot contenu principal
    AuthLayout.vue      # page login, fond épuré
    BetaLayout.vue      # layout réduit pour bêta-lecteurs
  pages/
    auth/
      LoginPage.vue
    dashboard/
      DashboardPage.vue
    editor/
      EditorPage.vue    # /projects/:id/edit
    cards/
      CardsPage.vue     # /projects/:id/cards
    notebook/
      NotebookPage.vue  # vue dédiée carnet depuis dashboard
    settings/
      SettingsPage.vue  # types de fiches, préférences projet
    profile/
      ProfilePage.vue
  router/
    index.ts
    guards.ts
  services/             # couche API axios
    auth.service.ts
    projects.service.ts
    chapters.service.ts
    scenes.service.ts
    cards.service.ts
    annotations.service.ts
    notebook.service.ts
    keywords.service.ts
  stores/
    auth.store.ts
    projects.store.ts
    editor.store.ts
    cards.store.ts
    notebook.store.ts
    theme.store.ts
  plugins/
    notebook.plugin.ts  # enregistre NotebookDrawer globalement
  types/                # interfaces TypeScript légères (juste les contrats API)
  App.vue
  main.ts
```

---

## 3. Thème clair / sombre

### 3.1 Configuration Tailwind v4

Tailwind CSS 4 utilise une approche CSS-first : pas de `tailwind.config.js`. Toute la configuration se fait dans `src/assets/main.css`.

```css
/* src/assets/main.css */
@import "tailwindcss";

/* Mode sombre via classe .dark sur <html> */
@variant dark (&:where(.dark, .dark *));

/* Palette de couleurs personnalisée */
@theme {
  --color-brand-50:  #EEEDFE;
  --color-brand-200: #AFA9EC;
  --color-brand-400: #7F77DD;
  --color-brand-600: #534AB7;
  --color-brand-800: #3C3489;
}

/* Utilitaires composants réutilisables */
@layer components {
  .btn-ghost {
    @apply flex items-center rounded-md px-2 py-1.5 text-sm
           text-gray-700 dark:text-gray-300
           hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors;
  }
  .dropdown-item {
    @apply block px-3 py-2 text-sm text-gray-700 dark:text-gray-300
           hover:bg-gray-100 dark:hover:bg-gray-800 cursor-pointer;
  }
}
```

Le plugin Tailwind est intégré directement dans Vite via `@tailwindcss/vite` (pas de PostCSS) :

```ts
// vite.config.ts
import tailwindcss from '@tailwindcss/vite'
import { VitePWA } from 'vite-plugin-pwa'

export default defineConfig({
  plugins: [vue(), vueDevTools(), tailwindcss(), VitePWA({ ... })],
  resolve: { alias: { '@': fileURLToPath(new URL('./src', import.meta.url)) } },
})
```

> **Note** : `vite-plugin-pwa` n'est pas encore compatible Vite 8 en peer dependencies. Installer avec `npm install vite-plugin-pwa --legacy-peer-deps`.

### 3.2 Anti-flash FOUC

```html
<!-- index.html — AVANT le bundle Vue, dans <head> -->
<script>
  const stored = localStorage.getItem('theme')
  const prefersDark = matchMedia('(prefers-color-scheme: dark)').matches
  const theme = stored ?? (prefersDark ? 'dark' : 'light')
  document.documentElement.classList.toggle('dark', theme === 'dark')
</script>
```

### 3.3 Store thème

```ts
// stores/theme.store.ts
export const useThemeStore = defineStore('theme', () => {
  const theme = ref<'light' | 'dark' | 'system'>(
    (localStorage.getItem('theme') as any) ?? 'system'
  )

  const applied = computed(() => {
    if (theme.value === 'system')
      return matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'
    return theme.value
  })

  function setTheme(value: 'light' | 'dark' | 'system') {
    theme.value = value
    localStorage.setItem('theme', value)
    document.documentElement.classList.toggle('dark', applied.value === 'dark')
  }

  // Cycle light → dark → system (utilisé dans la topbar)
  function cycleTheme() {
    const next = { light: 'dark', dark: 'system', system: 'light' } as const
    setTheme(next[theme.value])
  }

  // Écoute les changements système
  matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
    if (theme.value === 'system')
      document.documentElement.classList.toggle('dark', applied.value === 'dark')
  })

  return { theme, applied, setTheme, cycleTheme }
})
```

### 3.4 Toggle dans la topbar

Trois états cycliques : `light → dark → system`. Icônes : soleil / lune / écran.

```vue
<!-- Dans AppTopbar.vue, dropdown profil -->
<button @click="themeStore.setTheme(nextTheme)">
  <SunIcon v-if="themeStore.theme === 'light'" />
  <MoonIcon v-else-if="themeStore.theme === 'dark'" />
  <MonitorIcon v-else />
  {{ themeStore.theme === 'system' ? 'Système' : themeStore.theme }}
</button>
```

---

## 4. Topbar globale — AppTopbar.vue

Présente dans `AppLayout.vue`, donc visible sur toutes les pages authentifiées.

```
[ Logo / Nom app ]  [ Nom du projet courant (si dans un projet) ]  [ ... ]  [ Carnet ]  [ Avatar ▼ ]
```

### Contenu du dropdown avatar

- Nom + email de l'utilisateur
- Lien → Profil
- Lien → Paramètres
- Séparateur
- Toggle thème (light / dark / système)
- Séparateur
- Bouton Déconnexion

```vue
<!-- AppTopbar.vue structure -->
<template>
  <header class="flex items-center h-12 px-4 border-b border-border
                 bg-background dark:bg-background-dark">
    <RouterLink to="/dashboard" class="font-medium text-brand-600">
      Papyrus
    </RouterLink>

    <span v-if="editorStore.currentProject" class="ml-4 text-sm text-muted">
      {{ editorStore.currentProject.title }}
    </span>

    <div class="ml-auto flex items-center gap-3">
      <!-- Bouton carnet -->
      <button @click="notebookStore.toggleDrawer()" class="btn-ghost text-sm gap-1.5">
        <BookIcon class="w-4 h-4" />
        Carnet
      </button>

      <!-- Avatar dropdown -->
      <div class="relative" ref="dropdownRef">
        <button @click="dropdownOpen = !dropdownOpen"
                class="flex items-center gap-2 rounded-full">
          <Avatar :initials="authStore.user?.name.slice(0,2)" size="sm" />
        </button>

        <Transition name="dropdown">
          <div v-if="dropdownOpen"
               class="absolute right-0 top-10 w-52 bg-background border border-border
                      rounded-lg shadow-sm z-50 py-1 dark:bg-background-dark">
            <div class="px-3 py-2 border-b border-border">
              <p class="text-sm font-medium">{{ authStore.user?.name }}</p>
              <p class="text-xs text-muted">{{ authStore.user?.email }}</p>
            </div>
            <RouterLink to="/profile" class="dropdown-item">Profil</RouterLink>
            <RouterLink to="/settings" class="dropdown-item">Paramètres</RouterLink>
            <div class="border-t border-border my-1" />
            <button @click="cycleTheme" class="dropdown-item w-full text-left flex gap-2">
              <ThemeIcon /> {{ themeLabel }}
            </button>
            <div class="border-t border-border my-1" />
            <button @click="logout" class="dropdown-item w-full text-left text-red-600">
              Déconnexion
            </button>
          </div>
        </Transition>
      </div>
    </div>
  </header>
</template>
```

---

## 5. Vue Router

### 5.1 Routes

```ts
// router/index.ts
const routes = [
  {
    path: '/login',
    component: AuthLayout,
    children: [{ path: '', name: 'login', component: LoginPage }]
  },
  {
    path: '/',
    component: AppLayout,
    meta: { requiresAuth: true },
    children: [
      { path: '',          redirect: '/dashboard' },
      { path: 'dashboard', name: 'dashboard',    component: DashboardPage },
      { path: 'notebook',  name: 'notebook',     component: NotebookPage },
      { path: 'profile',   name: 'profile',      component: ProfilePage },
      { path: 'settings',  name: 'settings',     component: SettingsPage },
      {
        path: 'projects/:projectId',
        meta: { requiresProject: true },
        children: [
          { path: 'edit',  name: 'editor',  component: EditorPage },
          { path: 'cards', name: 'cards',   component: CardsPage },
        ]
      }
    ]
  },
  {
    path: '/beta/:token',
    name: 'beta-reader',
    component: BetaReaderPage,
    meta: { betaReader: true }
  }
]
```

### 5.2 Guards

```ts
// router/guards.ts
router.beforeEach(async (to) => {
  const auth = useAuthStore()

  // Route protégée mais non authentifié
  if (to.meta.requiresAuth && !auth.isAuthenticated) {
    await auth.tryRestoreSession()
    if (!auth.isAuthenticated) return { name: 'login' }
  }

  // Vérifier l'accès au projet
  if (to.meta.requiresProject && to.params.projectId) {
    const projects = useProjectsStore()
    const ok = await projects.checkAccess(to.params.projectId as string)
    if (!ok) return { name: 'dashboard' }
  }

  // Bêta-lecteur ne peut pas accéder aux routes auteur
  if (!to.meta.betaReader && auth.isBetaReader) {
    return { name: 'dashboard' }
  }
})
```

---

## 6. Stores Pinia

### 6.1 useAuthStore

```ts
export const useAuthStore = defineStore('auth', () => {
  const user = ref<User | null>(null)
  const isAuthenticated = computed(() => !!user.value)
  const isBetaReader = computed(() =>
    user.value?.currentRole === 'beta_reader'
  )

  async function login(email: string, password: string) {
    await authService.csrfCookie()
    const data = await authService.login(email, password)
    user.value = data.user
  }

  async function logout() {
    await authService.logout()
    user.value = null
    router.push({ name: 'login' })
  }

  async function tryRestoreSession() {
    try {
      user.value = await authService.me()
    } catch {
      user.value = null
    }
  }

  return { user, isAuthenticated, isBetaReader, login, logout, tryRestoreSession }
})
```

### 6.2 useProjectsStore

```ts
export const useProjectsStore = defineStore('projects', () => {
  const projects = ref<Project[]>([])
  const loading = ref(false)

  async function fetchAll() {
    loading.value = true
    projects.value = await projectsService.index()
    loading.value = false
  }

  async function create(payload: CreateProjectPayload) {
    const project = await projectsService.store(payload)
    projects.value.unshift(project)
    return project
  }

  async function checkAccess(projectId: string): Promise<boolean> {
    // Vérifie que le projet est dans la liste ou le charge
    if (!projects.value.length) await fetchAll()
    return projects.value.some(p => p.id === projectId)
  }

  return { projects, loading, fetchAll, create, checkAccess }
})
```

### 6.3 useEditorStore

```ts
export const useEditorStore = defineStore('editor', () => {
  const currentProject = ref<Project | null>(null)
  const chapters = ref<Chapter[]>([])
  const activeScene = ref<Scene | null>(null)
  const saving = ref(false)
  const saveTimeout = ref<ReturnType<typeof setTimeout> | null>(null)

  // Autosave avec debounce 1.5s
  function onContentChange(content: string) {
    if (!activeScene.value) return
    activeScene.value.content = content
    if (saveTimeout.value) clearTimeout(saveTimeout.value)
    saveTimeout.value = setTimeout(() => saveScene(), 1500)
  }

  async function saveScene() {
    if (!activeScene.value) return
    saving.value = true
    await scenesService.update(activeScene.value.id, {
      content: activeScene.value.content,
      title: activeScene.value.title,
      status: activeScene.value.status,
    })
    saving.value = false
  }

  async function loadProject(projectId: string) {
    const [project, chaps] = await Promise.all([
      projectsService.show(projectId),
      chaptersService.index(projectId),
    ])
    currentProject.value = project
    chapters.value = chaps
  }

  return { currentProject, chapters, activeScene, saving, onContentChange, saveScene, loadProject }
})
```

### 6.4 useCardsStore

```ts
export const useCardsStore = defineStore('cards', () => {
  const cards = ref<Card[]>([])
  const activeCard = ref<Card | null>(null)
  const occurrences = ref<KeywordOccurrence[]>([])
  const rebuildStatus = ref<'idle' | 'pending' | 'done'>('idle')

  async function fetchForProject(projectId: string) {
    cards.value = await cardsService.index(projectId)
  }

  async function rebuildIndex(projectId: string) {
    rebuildStatus.value = 'pending'
    await keywordsService.rebuildIndex(projectId)
    rebuildStatus.value = 'done'
    // Recharger les occurrences de la carte active
    if (activeCard.value) {
      occurrences.value = await keywordsService.occurrences(activeCard.value.id)
    }
  }

  return { cards, activeCard, occurrences, rebuildStatus, fetchForProject, rebuildIndex }
})
```

### 6.5 useNotebookStore

```ts
export const useNotebookStore = defineStore('notebook', () => {
  const entries = ref<NotebookEntry[]>([])
  const drawerOpen = ref(false)
  const activeEntry = ref<NotebookEntry | null>(null)
  const composing = ref(false)

  function toggleDrawer() { drawerOpen.value = !drawerOpen.value }
  function openNew() { composing.value = true; activeEntry.value = null; drawerOpen.value = true }

  async function fetchAll(filters?: { projectId?: string; free?: boolean }) {
    entries.value = await notebookService.index(filters)
  }

  async function save(payload: Partial<NotebookEntry>) {
    if (activeEntry.value) {
      const updated = await notebookService.update(activeEntry.value.id, payload)
      const idx = entries.value.findIndex(e => e.id === updated.id)
      if (idx !== -1) entries.value[idx] = updated
      activeEntry.value = updated
    } else {
      const created = await notebookService.store(payload)
      entries.value.unshift(created)
      activeEntry.value = created
      composing.value = false
    }
  }

  async function remove(id: string) {
    await notebookService.destroy(id)
    entries.value = entries.value.filter(e => e.id !== id)
    activeEntry.value = null
  }

  return { entries, drawerOpen, activeEntry, composing, toggleDrawer, openNew, fetchAll, save, remove }
})
```

---

## 7. Couche services (axios)

### 7.1 Instance axios configurée

```ts
// services/api.ts
import axios from 'axios'

const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL ?? '/api/v1',
  withCredentials: true,         // cookies Sanctum
  withXSRFToken: true,           // envoie automatiquement le token XSRF-TOKEN
  headers: { 'Accept': 'application/json' }
})

// Intercepteur réponse — redirige vers /login si 401
api.interceptors.response.use(
  res => res,
  err => {
    if (err.response?.status === 401) {
      useAuthStore().user = null
      router.push({ name: 'login' })
    }
    return Promise.reject(err)
  }
)

export default api
```

### 7.2 Exemple service

```ts
// services/scenes.service.ts
export const scenesService = {
  show:   (id: string) =>
    api.get(`/scenes/${id}`).then(r => r.data.data),

  update: (id: string, payload: Partial<Scene>) =>
    api.put(`/scenes/${id}`, payload).then(r => r.data.data),

  reorder: (items: { id: string; order: number }[]) =>
    api.post('/scenes/reorder', { items }).then(r => r.data),
}
```

---

## 8. Composant NotebookDrawer — plugin global

### 8.1 Plugin

```ts
// plugins/notebook.plugin.ts
import NotebookDrawer from '@/components/notebook/NotebookDrawer.vue'

export default {
  install(app: App) {
    app.component('NotebookDrawer', NotebookDrawer)
  }
}
```

```ts
// main.ts
import NotebookPlugin from '@/plugins/notebook.plugin'
app.use(NotebookPlugin)
```

### 8.2 Montage dans AppLayout

Le drawer est monté une seule fois dans `AppLayout.vue` — il est donc disponible sur toutes les pages authentifiées sans re-mount.

```vue
<!-- layouts/AppLayout.vue -->
<template>
  <div class="flex flex-col h-screen bg-background dark:bg-background-dark">
    <AppTopbar />
    <main class="flex-1 overflow-hidden relative">
      <RouterView />
      <!-- Drawer carnet — monté une fois, toujours présent -->
      <NotebookDrawer />
    </main>
  </div>
</template>
```

### 8.3 Ouverture depuis n'importe où

```ts
// Dans n'importe quel composant
const notebook = useNotebookStore()
notebook.toggleDrawer()   // ouvre/ferme
notebook.openNew()        // ouvre en mode composition
```

---

## 9. Composants clés

### 9.1 SceneEditor.vue

```
Props  : scene: Scene, projectId: string
Emits  : update:scene, annotate(anchor)
Stores : useEditorStore, useCardsStore

Responsabilités :
- Éditeur texte (contenteditable ou tiptap minimal)
- Surbrillance des mots-clés (computed depuis keyword_occurrences)
- Soulignement des annotations auteur (anchor_start / anchor_end)
- Autosave via editorStore.onContentChange()
- Barre d'outils formatting
- Footer chips fiches liées
```

> Pour la surbrillance des mots-clés, transformer le contenu en HTML avec `<mark>` autour des occurrences côté frontend, à partir des `position_start/end` stockés dans `keyword_occurrences`.

### 9.2 AnnotationPanel.vue

```
Props  : sceneId: string, role: 'owner'|'co_author'|'beta_reader'
Stores : useEditorStore

- Liste les annotations de la scène
- Filtre inline vs global
- Bouton "+ annotation" → sélection d'un anchor dans l'éditeur
- Chips fiches liées (cachées pour beta_reader)
```

### 9.3 CardDetail.vue

```
Props  : cardId: string
Stores : useCardsStore

Onglets :
  - Attributs  : affichage/édition des card_attributes (form dynamique)
  - Liaisons   : liste des card_links avec picker de fiches
  - Référentiel : keyword_occurrences avec bouton ↻ recalculer
```

### 9.4 ProjectCard.vue

```
Props  : project: Project, view: 'grid'|'list'
Emits  : open, manage-access

- Tranche colorée (couleur projet)
- Barres de progression mots + scènes
- Avatars membres
- Boutons Ouvrir / Accès
```

---

## 10. PWA

```ts
// vite.config.ts — extrait plugin PWA uniquement
VitePWA({
  registerType: 'autoUpdate',
  manifest: {
    name: 'Papyrus',
    short_name: 'Papyrus',
    theme_color: '#534AB7',
    background_color: '#ffffff',
    display: 'standalone',
    icons: [
      { src: '/icon-192.png', sizes: '192x192', type: 'image/png' },
      { src: '/icon-512.png', sizes: '512x512', type: 'image/png' },
    ]
  },
  workbox: {
    // Pas d'offline — juste installable
    // On pré-cache uniquement les assets statiques
    globPatterns: ['**/*.{js,css,html,ico,png,svg}'],
    runtimeCaching: []
  }
})
```

---

## 11. Variables d'environnement

```env
# .env (développement local)
VITE_API_URL=http://localhost:8000/api/v1

# .env.production
VITE_API_URL=https://papyrus.local/api/v1
```

---

## 12. Points d'attention

- **Autosave** — debounce 1500ms dans `useEditorStore`. Afficher un indicateur "Enregistrement…" / "Enregistré" dans la topbar de l'éditeur.
- **Rôle beta_reader** — masquer systématiquement les composants Cards, Notes auteur, Carnet via `v-if="!authStore.isBetaReader"`. Ne pas se contenter du guard router.
- **Surbrillance mots-clés** — calculée côté frontend à partir des `position_start/end`. Attention aux décalages si le contenu a des balises HTML — travailler sur le texte brut strippé, comme le fait `KeywordScanner` côté backend.
- **Drag & drop** — pour le réordonnancement des scènes et chapitres, utiliser `@vueuse/core` `useDraggable` ou `vuedraggable`. Envoyer le nouvel ordre via `POST /reorder` uniquement au drop (pas à chaque mouvement).
- **Thème** — toujours tester les deux modes avant de livrer un composant. Utiliser exclusivement les classes Tailwind `dark:` — pas de styles inline hardcodés.
- **Pagination** — tous les index API sont paginés. Implémenter un scroll infini ou une pagination classique selon le contexte (scroll infini pour les scènes, pagination pour le carnet et les fiches).
