import { ref } from 'vue'
import { defineStore } from 'pinia'
export const useToastStore = defineStore('toast', () => {
  const messages = ref([])
  function show(message, type = 'info', duration = 3500) { const id = Date.now() + Math.random(); messages.value.push({ id, message, type }); window.setTimeout(() => dismiss(id), duration); return id }
  function dismiss(id) { messages.value = messages.value.filter((toast) => toast.id !== id) }
  return { messages, show, dismiss, success: (message) => show(message, 'success'), error: (message) => show(message, 'error'), warning: (message) => show(message, 'warning'), info: (message) => show(message, 'info') }
})
