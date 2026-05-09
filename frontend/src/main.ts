import './assets/main.css'
import { installConsoleInterceptor } from '@/composables/bugBuffer'
installConsoleInterceptor()

import { registerSW } from 'virtual:pwa-register'
registerSW({
  immediate: true,
  onRegisteredSW(_, registration) {
    // Vérifie les mises à jour toutes les heures
    setInterval(() => registration?.update(), 60 * 60 * 1000)
  },
})

import { createApp } from 'vue'
import { createPinia } from 'pinia'
import PrimeVue from 'primevue/config'

import App from './App.vue'
import router from './router'
import NotebookPlugin from './plugins/notebook.plugin'

const app = createApp(App)

app.use(createPinia())
app.use(router)
app.use(NotebookPlugin)
app.use(PrimeVue, { unstyled: true })

app.mount('#app')
