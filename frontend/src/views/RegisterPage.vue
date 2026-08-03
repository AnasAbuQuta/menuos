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
const form = reactive({ name: '', email: '', password: '', password_confirmation: '' })
const error = ref('')
const loading = ref(false)

async function submit() {
  if (loading.value) return
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
    <BaseCard>
      <form class="auth-card" @submit.prevent="submit">
        <div><p class="eyebrow">MenuOS</p><h1>Create your account</h1><p>Start with your owner profile.</p></div>
        <BaseAlert v-if="error" type="error">{{ error }}</BaseAlert>
        <BaseInput v-model="form.name" label="Name" autocomplete="name" maxlength="255" required autofocus />
        <BaseInput v-model="form.email" label="Email" type="email" autocomplete="email" required />
        <BaseInput v-model="form.password" label="Password" type="password" autocomplete="new-password" minlength="8" hint="At least 8 characters" required />
        <BaseInput v-model="form.password_confirmation" label="Confirm password" type="password" autocomplete="new-password" required />
        <BaseButton type="submit" :loading="loading">Create account</BaseButton>
        <p class="form-footer">Already registered? <RouterLink to="/login">Sign in</RouterLink></p>
      </form>
    </BaseCard>
  </main>
</template>
