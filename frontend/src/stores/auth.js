import { computed, ref } from 'vue'
import { defineStore } from 'pinia'
import api, { TOKEN_KEY } from '../services/api'

export const useAuthStore = defineStore('auth', () => {
  const token = ref(localStorage.getItem(TOKEN_KEY))
  const user = ref(null)
  const initialized = ref(false)
  const isAuthenticated = computed(() => Boolean(token.value && user.value))
  const hasRestaurant = computed(() => Boolean(user.value?.restaurant))
  const isSuperAdmin = computed(() => Boolean(user.value?.is_super_admin))

  function setSession(data) {
    token.value = data.token
    user.value = data.user
    localStorage.setItem(TOKEN_KEY, data.token)
  }

  function clearSession() {
    token.value = null
    user.value = null
    localStorage.removeItem(TOKEN_KEY)
  }

  async function register(payload) {
    const { data } = await api.post('/auth/register', payload)
    setSession(data.data)
  }

  async function login(payload) {
    const { data } = await api.post('/auth/login', payload)
    setSession(data.data)
  }

  async function restore() {
    if (initialized.value) return

    if (token.value) {
      try {
        const { data } = await api.get('/auth/me')
        user.value = data.data.user
      } catch {
        clearSession()
      }
    }

    initialized.value = true
  }

  async function logout() {
    try {
      await api.post('/auth/logout')
    } finally {
      clearSession()
    }
  }

  async function createRestaurant(payload) {
    const { data } = await api.post('/restaurant', payload)
    user.value.restaurant = data.data.restaurant
  }

  return {
    user, initialized, isAuthenticated, hasRestaurant, isSuperAdmin,
    register, login, restore, logout, createRestaurant,
  }
})
