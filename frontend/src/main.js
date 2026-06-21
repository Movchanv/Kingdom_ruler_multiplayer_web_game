import { createApp } from 'vue'
import { createPinia } from 'pinia'
import Toast from 'vue-toastification'
import 'vue-toastification/dist/index.css'

import App from '@/App.vue'
import router from '@/router'
import { toastOptions } from '@/plugins/toast'
import { useAuthStore } from '@/stores/auth'
import '@/styles/main.css'

const app = createApp(App)
const pinia = createPinia()

app.use(pinia)
app.use(Toast, toastOptions)
app.use(router)

const auth = useAuthStore()
auth
  .fetchUser()
  .catch(() => {s
  })
  .finally(() => {
    app.mount('#app')
  })
