<script setup>
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import { apiError } from '../services/api'
import BaseAlert from '../components/ui/BaseAlert.vue'
import BaseButton from '../components/ui/BaseButton.vue'
import BaseCard from '../components/ui/BaseCard.vue'
import BaseInput from '../components/ui/BaseInput.vue'

const auth = useAuthStore()
const router = useRouter()
const form = reactive({ email: '', password: '' })
const error = ref('')
const loading = ref(false)

async function submit() {
  if (loading.value) return
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
    <BaseCard>
      <form class="auth-card" @submit.prevent="submit">
        <div><p class="eyebrow">MenuOS</p><h1>Welcome back</h1><p>Sign in to manage your restaurant.</p></div>
        <BaseAlert v-if="error" type="error">{{ error }}</BaseAlert>
        <BaseInput v-model="form.email" label="Email" type="email" autocomplete="email" required autofocus />
        <BaseInput v-model="form.password" label="Password" type="password" autocomplete="current-password" required />
        <BaseButton type="submit" :loading="loading">Sign in</BaseButton>
        <p class="form-footer">New to MenuOS? <RouterLink to="/register">Create an account</RouterLink></p>
      </form>
    </BaseCard>
  </main>
</template>
