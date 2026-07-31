<script setup>
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import { apiError } from '../services/api'

const auth = useAuthStore()
const router = useRouter()
const form = reactive({ email: '', password: '' })
const error = ref('')
const loading = ref(false)

async function submit() {
  error.value = ''
  loading.value = true
  try {
    await auth.login(form)
    await router.push({ name: auth.hasRestaurant ? 'dashboard' : 'restaurant-setup' })
  } catch (requestError) {
    error.value = apiError(requestError, 'Unable to log in.')
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <main class="auth-page">
    <form class="card auth-card" @submit.prevent="submit">
      <div><p class="eyebrow">MenuOS</p><h1>Welcome back</h1><p>Sign in to manage your restaurant.</p></div>
      <p v-if="error" class="error" role="alert">{{ error }}</p>
      <label>Email<input v-model.trim="form.email" type="email" autocomplete="email" required></label>
      <label>Password<input v-model="form.password" type="password" autocomplete="current-password" required></label>
      <button class="button" type="submit" :disabled="loading">{{ loading ? 'Signing in…' : 'Sign in' }}</button>
      <p class="form-footer">New to MenuOS? <RouterLink to="/register">Create an account</RouterLink></p>
    </form>
  </main>
</template>
