<script setup>
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import { apiError } from '../services/api'
import BaseAlert from '../components/ui/BaseAlert.vue'
import BaseButton from '../components/ui/BaseButton.vue'
import BaseInput from '../components/ui/BaseInput.vue'
import BaseTextarea from '../components/ui/BaseTextarea.vue'
import { useI18n } from 'vue-i18n'

const auth = useAuthStore()
const router = useRouter()
const form = reactive({ name_ar: '', name_en: '', description_ar: '', description_en: '', default_language: 'ar', whatsapp: '', phone: '', address: '', currency: 'ILS', primary_color: '' })
const error = ref('')
const loading = ref(false)
const { t } = useI18n()

async function submit() {
  if (loading.value) return
  error.value = ''
  loading.value = true
  const payload = Object.fromEntries(Object.entries(form).filter(([, value]) => value !== ''))
  try {
    await auth.createRestaurant(payload)
    await router.push({ name: 'dashboard' })
  } catch (requestError) {
    error.value = apiError(requestError, t('restaurant.createError'))
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <section class="card setup-card">
    <div><p class="eyebrow">{{ $t('restaurant.setup') }}</p><h1>{{ $t('restaurant.setupTitle') }}</h1><p>{{ $t('restaurant.setupIntro') }}</p></div>
    <BaseAlert v-if="error" type="error">{{ error }}</BaseAlert>
    <form class="form-grid" @submit.prevent="submit">
      <fieldset class="full"><legend>{{ $t('restaurant.arabicContent') }}</legend><BaseInput v-model="form.name_ar" full :label="$t('restaurant.nameAr')" maxlength="255" autofocus /><BaseTextarea v-model="form.description_ar" full :label="$t('restaurant.descriptionAr')" :maxlength="5000" rows="3" /></fieldset>
      <fieldset class="full"><legend>{{ $t('restaurant.englishContent') }}</legend><BaseInput v-model="form.name_en" full :label="$t('restaurant.nameEn')" maxlength="255" /><BaseTextarea v-model="form.description_en" full :label="$t('restaurant.descriptionEn')" :maxlength="5000" rows="3" /></fieldset>
      <label>{{ $t('restaurant.defaultLanguage') }}<select v-model="form.default_language"><option value="ar">{{ $t('language.ar') }}</option><option value="en">{{ $t('language.en') }}</option></select></label>
      <BaseInput v-model="form.whatsapp" :label="$t('restaurant.whatsapp')" type="tel" maxlength="30" />
      <BaseInput v-model="form.phone" :label="$t('restaurant.phone')" type="tel" maxlength="30" />
      <BaseTextarea v-model="form.address" full :label="$t('restaurant.address')" :maxlength="2000" rows="2" />
      <BaseInput v-model="form.currency" :label="$t('restaurant.currency')" maxlength="3" required />
      <label>{{ $t('restaurant.color') }}<input v-model="form.primary_color" type="color"></label>
      <BaseButton class="full" type="submit" :loading="loading">{{ $t('restaurant.create') }}</BaseButton>
    </form>
  </section>
</template>
