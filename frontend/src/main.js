import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './App.vue'
import router from './router'
import { i18n } from './i18n'
import './style.css'

createApp(App).use(createPinia()).use(i18n).use(router).mount('#app')

if ('serviceWorker' in navigator && import.meta.env.PROD) window.addEventListener('load', async () => {
  const registration = await navigator.serviceWorker.register('/sw.js')
  await registration.update()
  window.setInterval(() => registration.update(), 60 * 60 * 1000)
})
