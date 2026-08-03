<script setup>
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import { apiError } from '../services/api'
import BaseAlert from '../components/ui/BaseAlert.vue'
import BaseButton from '../components/ui/BaseButton.vue'
import BaseInput from '../components/ui/BaseInput.vue'
import BaseTextarea from '../components/ui/BaseTextarea.vue'

const auth = useAuthStore()
const router = useRouter()
const form = reactive({ name: '', description: '', whatsapp: '', phone: '', address: '', currency: 'ILS', primary_color: '' })
const error = ref('')
const loading = ref(false)

async function submit() {
  if (loading.value) return
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
    <BaseAlert v-if="error" type="error">{{ error }}</BaseAlert>
    <form class="form-grid" @submit.prevent="submit">
      <BaseInput v-model="form.name" full label="Restaurant name" maxlength="255" required autofocus />
      <BaseTextarea v-model="form.description" full label="Description" :maxlength="5000" rows="3" />
      <BaseInput v-model="form.whatsapp" label="WhatsApp" type="tel" maxlength="30" hint="Include country code" />
      <BaseInput v-model="form.phone" label="Phone" type="tel" maxlength="30" />
      <BaseTextarea v-model="form.address" full label="Address" :maxlength="2000" rows="2" />
      <BaseInput v-model="form.currency" label="Currency" maxlength="3" required />
      <label>Primary color<input v-model="form.primary_color" type="color"></label>
      <BaseButton class="full" type="submit" :loading="loading">Create restaurant</BaseButton>
    </form>
  </section>
</template>
