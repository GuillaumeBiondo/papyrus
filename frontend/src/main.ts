import './assets/main.css'
import { installConsoleInterceptor } from '@/composables/bugBuffer'
installConsoleInterceptor()

if ('serviceWorker' in navigator) {
  let reloading = false
  navigator.serviceWorker.addEventListener('controllerchange', () => {
    if (!reloading) {
      reloading = true
      window.location.reload()
    }
  })
}


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
