<script setup>
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import { apiError } from '../services/api'

const auth = useAuthStore()
const router = useRouter()
const form = reactive({ name: '', email: '', password: '', password_confirmation: '' })
const error = ref('')
const loading = ref(false)

async function submit() {
  error.value = ''
  loading.value = true
  try {
    await auth.register(form)
    await router.push({ name: 'restaurant-setup' })
  } catch (requestError) {
    error.value = apiError(requestError, 'Unable to create your account.')
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <main class="auth-page">
    <form class="card auth-card" @submit.prevent="submit">
      <div><p class="eyebrow">MenuOS</p><h1>Create your account</h1><p>Start with your owner profile.</p></div>
      <p v-if="error" class="error" role="alert">{{ error }}</p>
      <label>Name<input v-model.trim="form.name" autocomplete="name" required></label>
      <label>Email<input v-model.trim="form.email" type="email" autocomplete="email" required></label>
      <label>Password<input v-model="form.password" type="password" autocomplete="new-password" minlength="8" required></label>
      <label>Confirm password<input v-model="form.password_confirmation" type="password" autocomplete="new-password" required></label>
      <button class="button" type="submit" :disabled="loading">{{ loading ? 'Creating…' : 'Create account' }}</button>
      <p class="form-footer">Already registered? <RouterLink to="/login">Sign in</RouterLink></p>
    </form>
  </main>
</template>
