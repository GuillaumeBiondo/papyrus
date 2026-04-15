import type { App } from 'vue'
import NotebookDrawer from '@/components/notebook/NotebookDrawer.vue'

export default {
  install(app: App) {
    app.component('NotebookDrawer', NotebookDrawer)
  },
}
