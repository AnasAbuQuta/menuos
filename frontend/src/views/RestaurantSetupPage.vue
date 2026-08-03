<script setup>
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '../stores/auth'
import { apiError } from '../services/api'
import BaseAlert from '../components/ui/BaseAlert.vue'
import BaseButton from '../components/ui/BaseButton.vue'
import BaseInput from '../components/ui/BaseInput.vue'
import BaseTextarea from '../components/ui/BaseTextarea.vue'
import CurrencySelect from '../components/CurrencySelect.vue'
import ThemeSelector from '../components/ThemeSelector.vue'

const days = ['saturday', 'sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday']
const auth = useAuthStore()
const router = useRouter()
const { t } = useI18n()
const error = ref('')
const loading = ref(false)
const form = reactive({ name_ar: '', name_en: '', description_ar: '', description_en: '', default_language: 'ar', whatsapp: '', phone: '', address: '', currency: 'ILS', theme_key: 'modern', primary_color: '', opening_hours: Object.fromEntries(days.map((day) => [day, { is_open: day !== 'friday', open: day === 'friday' ? null : '09:00', close: day === 'friday' ? null : '23:00' }])) })

function toggleDay(day) {
  const hours = form.opening_hours[day]
  if (!hours.is_open) { hours.open = null; hours.close = null }
  else { hours.open ||= '09:00'; hours.close ||= '23:00' }
}

async function submit() {
  if (loading.value) return
  error.value = ''; loading.value = true
  const payload = structuredClone(form)
  if (!payload.primary_color) payload.primary_color = null
  try { await auth.createRestaurant(payload); await router.push({ name: 'restaurant-settings' }) }
  catch (requestError) { error.value = apiError(requestError, t('restaurant.createError')) }
  finally { loading.value = false }
}
</script>

<template>
  <section class="setup-page">
    <div class="page-heading"><div><p class="eyebrow">{{ $t('restaurant.setup') }}</p><h1>{{ $t('restaurant.setupTitle') }}</h1><p>{{ $t('restaurant.setupIntro') }}</p></div></div>
    <BaseAlert v-if="error" type="error">{{ error }}</BaseAlert>
    <form class="setup-sections" @submit.prevent="submit">
      <section class="card settings-section"><div><h2>{{ $t('restaurant.basic') }}</h2></div><div class="settings-grid"><BaseInput v-model="form.name_ar" :label="$t('restaurant.nameAr')" maxlength="255" autofocus /><BaseInput v-model="form.name_en" :label="$t('restaurant.nameEn')" maxlength="255" /><BaseTextarea v-model="form.description_ar" full :label="$t('restaurant.descriptionAr')" :maxlength="5000" rows="3" /><BaseTextarea v-model="form.description_en" full :label="$t('restaurant.descriptionEn')" :maxlength="5000" rows="3" /></div></section>
      <section class="card settings-section"><div><h2>{{ $t('themes.languages') }}</h2></div><label>{{ $t('restaurant.defaultLanguage') }}<select v-model="form.default_language"><option value="ar">{{ $t('language.ar') }}</option><option value="en">{{ $t('language.en') }}</option></select></label></section>
      <section class="card settings-section"><div><h2>{{ $t('restaurant.contact') }}</h2></div><div class="settings-grid"><BaseInput v-model="form.whatsapp" :label="$t('restaurant.whatsapp')" type="tel" maxlength="30" /><BaseInput v-model="form.phone" :label="$t('restaurant.phone')" type="tel" maxlength="30" /></div></section>
      <section class="card settings-section"><div><h2>{{ $t('themes.location') }}</h2></div><BaseTextarea v-model="form.address" full :label="$t('restaurant.address')" :maxlength="2000" rows="2" /></section>
      <section class="card settings-section"><div><h2>{{ $t('restaurant.currency') }}</h2></div><CurrencySelect v-model="form.currency" /></section>
      <section class="card settings-section"><div><h2>{{ $t('themes.title') }}</h2><p>{{ $t('themes.help') }}</p></div><ThemeSelector v-model="form.theme_key" v-model:primary-color="form.primary_color" /></section>
      <section class="card settings-section"><div><h2>{{ $t('restaurant.hours') }}</h2></div><div class="hours-list"><div v-for="day in days" :key="day" class="hours-row"><strong>{{ $t(`days.${day}`) }}</strong><label class="checkbox-label"><input v-model="form.opening_hours[day].is_open" type="checkbox" @change="toggleDay(day)">{{ $t('restaurant.open') }}</label><label>{{ $t('restaurant.opens') }}<input v-model="form.opening_hours[day].open" type="time" :disabled="!form.opening_hours[day].is_open"></label><label>{{ $t('restaurant.closes') }}<input v-model="form.opening_hours[day].close" type="time" :disabled="!form.opening_hours[day].is_open"></label></div></div></section>
      <section class="card settings-section"><div><h2>{{ $t('themes.images') }}</h2><p>{{ $t('themes.imagesAfterCreate') }}</p></div></section>
      <BaseButton class="settings-save" type="submit" :loading="loading">{{ $t('restaurant.create') }}</BaseButton>
    </form>
  </section>
</template>
