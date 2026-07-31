<script setup>
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import { apiError } from '../services/api'

const auth = useAuthStore()
const router = useRouter()
const form = reactive({ name: '', description: '', whatsapp: '', phone: '', address: '', currency: 'ILS', primary_color: '' })
const error = ref('')
const loading = ref(false)

async function submit() {
  error.value = ''
  loading.value = true
  const payload = Object.fromEntries(Object.entries(form).filter(([, value]) => value !== ''))
  try {
    await auth.createRestaurant(payload)
    await router.push({ name: 'dashboard' })
  } catch (requestError) {
    error.value = apiError(requestError, 'Unable to create your restaurant.')
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <section class="card setup-card">
    <div><p class="eyebrow">Restaurant setup</p><h1>Tell us about your restaurant</h1><p>You can update these details later.</p></div>
    <p v-if="error" class="error" role="alert">{{ error }}</p>
    <form class="form-grid" @submit.prevent="submit">
      <label class="full">Restaurant name<input v-model.trim="form.name" required></label>
      <label class="full">Description<textarea v-model.trim="form.description" rows="3" /></label>
      <label>WhatsApp<input v-model.trim="form.whatsapp" type="tel"></label>
      <label>Phone<input v-model.trim="form.phone" type="tel"></label>
      <label class="full">Address<textarea v-model.trim="form.address" rows="2" /></label>
      <label>Currency<input v-model.trim="form.currency" maxlength="3" required></label>
      <label>Primary color<input v-model="form.primary_color" type="color"></label>
      <button class="button full" type="submit" :disabled="loading">{{ loading ? 'Creating…' : 'Create restaurant' }}</button>
    </form>
  </section>
</template>
