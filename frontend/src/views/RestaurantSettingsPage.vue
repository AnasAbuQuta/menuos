<script setup>
import { computed, nextTick, onMounted, reactive, ref } from 'vue'
import RestaurantImageField from '../components/RestaurantImageField.vue'
import { useAuthStore } from '../stores/auth'
import { apiError } from '../services/api'
import { deleteCover, deleteLogo, getRestaurant, updateRestaurant, uploadCover, uploadLogo } from '../services/restaurant'
import BaseLoading from '../components/ui/BaseLoading.vue'
import BaseButton from '../components/ui/BaseButton.vue'
import { useToastStore } from '../stores/toast'
import { useI18n } from 'vue-i18n'
import PublicMenuPreview from '../components/PublicMenuPreview.vue'
import CurrencySelect from '../components/CurrencySelect.vue'
import ThemeSelector from '../components/ThemeSelector.vue'
import { copyHours, setTwentyFourHours } from '../utils/openingHours'
import { useLocalizedMeta } from '../composables/useLocalizedMeta'

const days = ['saturday', 'sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday']
const auth = useAuthStore()
const restaurant = ref(null)
const loading = ref(true)
const saving = ref(false)
const imageBusy = reactive({ logo: false, cover: false })
const imageProgress = reactive({ logo: 0, cover: 0 })
const pageError = ref('')
const errors = ref({})
const toast = useToastStore()
const logoField = ref(null)
const coverField = ref(null)
const form = reactive({ name_ar: '', name_en: '', description_ar: '', description_en: '', default_language: 'ar', is_active: true, whatsapp: '', phone: '', address: '', currency: 'ILS', theme_key: 'modern', primary_color: '', opening_hours: {} })
const colorValid = computed(() => !form.primary_color || /^#[0-9A-Fa-f]{6}$/.test(form.primary_color))
const { t } = useI18n()
useLocalizedMeta('meta.restaurant')

function defaultHours() {
  return Object.fromEntries(days.map((day) => [day, { is_open: day !== 'friday', open: day === 'friday' ? null : '09:00', close: day === 'friday' ? null : '23:00' }]))
}

function populate(data) {
  restaurant.value = data
  Object.assign(form, {
    name_ar: data.name_ar ?? data.name ?? '', name_en: data.name_en ?? '', description_ar: data.description_ar ?? data.description ?? '', description_en: data.description_en ?? '', default_language: data.default_language ?? 'ar', is_active: data.is_active,
    whatsapp: data.whatsapp ?? '', phone: data.phone ?? '', address: data.address ?? '',
    currency: data.currency ?? 'ILS', theme_key: data.theme_key ?? 'modern', primary_color: data.primary_color ?? '',
    opening_hours: data.opening_hours ?? defaultHours(),
  })
  auth.user.restaurant = data
}

async function load() {
  loading.value = true; pageError.value = ''
  try { populate(await getRestaurant()) }
  catch (error) { pageError.value = apiError(error, t('restaurant.loadError')) }
  finally { loading.value = false }
}

async function save() {
  if (saving.value) return
  errors.value = {}
  if (!form.name_ar.trim() && !form.name_en.trim()) { errors.value = { name_ar: [t('setup.nameRequired')] }; await focusFirstError(); return }
  if (!colorValid.value) { errors.value = { primary_color: [t('restaurant.colorInvalid')] }; await focusFirstError(); return }
  saving.value = true
  try {
    const payload = JSON.parse(JSON.stringify(form))
    payload.name_ar = payload.name_ar.trim() || null
    payload.name_en = payload.name_en.trim() || null
    payload.primary_color = payload.primary_color.toUpperCase()
    populate(await updateRestaurant(payload))
    toast.success(t('restaurant.saved'))
  } catch (error) { errors.value = error.response?.data?.errors ?? { general: [apiError(error, t('restaurant.saveError'))] }; await focusFirstError() }
  finally { saving.value = false }
}

function toggleDay(day) {
  const hours = form.opening_hours[day]
  if (!hours.is_open) { hours.open = null; hours.close = null }
  else { hours.open ||= '09:00'; hours.close ||= '23:00' }
}

function copyToAll(day) {
  days.forEach((target) => copyHours(form.opening_hours[day], form.opening_hours[target]))
}

async function handleImage(type, file) {
  imageBusy[type] = true; imageProgress[type] = 0; pageError.value = ''
  try {
    const progress = (value) => { imageProgress[type] = value }
    const updated = type === 'logo' ? await uploadLogo(file, progress) : await uploadCover(file, progress)
    populate(updated)
    if (type === 'logo') logoField.value?.reset(); else coverField.value?.reset()
    toast.success(t(type === 'logo' ? 'restaurant.logoUpdated' : 'restaurant.coverUpdated'))
  } catch (error) { pageError.value = apiError(error, t('restaurant.uploadError', { type: t(`imageEditor.${type}`) })) }
  finally { imageBusy[type] = false }
}

async function removeImage(type) {
  imageBusy[type] = true; pageError.value = ''
  try { populate(type === 'logo' ? await deleteLogo() : await deleteCover()); toast.success(t('restaurant.imageRemoved')) }
  catch (error) { pageError.value = apiError(error, t('restaurant.removeError', { type: t(`imageEditor.${type}`) })) }
  finally { imageBusy[type] = false }
}

function errorFor(field) { return errors.value[field]?.[0] || '' }
async function focusFirstError() {
  await nextTick()
  document.querySelector('.settings-form .field-error')?.closest('label')?.querySelector('input, textarea, select')?.focus()
}
onMounted(load)
</script>

<template>
  <section class="restaurant-settings-page">
    <div class="page-heading"><div><p class="eyebrow">{{ $t('restaurant.profile') }}</p><h1>{{ $t('restaurant.settings') }}</h1><p>{{ $t('restaurant.settingsIntro') }}</p></div><PublicMenuPreview :restaurant="restaurant" /></div>
    <BaseLoading v-if="loading" :rows="6" :label="t('restaurant.loading')" />
    <div v-else-if="pageError && !restaurant" class="error-state" role="alert"><p>{{ pageError }}</p><button class="button button-secondary" type="button" @click="load">{{ t('common.retry') }}</button></div>
    <template v-else>
      <p v-if="pageError" class="error" role="alert">{{ pageError }}</p>
      <p v-if="errors.general" class="error" role="alert">{{ errors.general[0] }}</p>
      <form class="settings-form" @submit.prevent="save">
        <section class="card settings-section"><div><h2>{{ $t('restaurant.basic') }}</h2><p>{{ $t('restaurant.settingsIntro') }}</p></div><div class="settings-grid"><label>{{ $t('restaurant.nameAr') }}<input v-model="form.name_ar" maxlength="255" :disabled="saving"><span v-if="errorFor('name_ar')" class="field-error">{{ errorFor('name_ar') }}</span></label><label>{{ $t('restaurant.nameEn') }}<input v-model="form.name_en" maxlength="255" :disabled="saving"><span v-if="errorFor('name_en')" class="field-error">{{ errorFor('name_en') }}</span></label><label class="full">{{ $t('restaurant.descriptionAr') }}<textarea v-model="form.description_ar" rows="4" maxlength="5000" :disabled="saving" /></label><label class="full">{{ $t('restaurant.descriptionEn') }}<textarea v-model="form.description_en" rows="4" maxlength="5000" :disabled="saving" /></label><label>{{ $t('restaurant.defaultLanguage') }}<select v-model="form.default_language" :disabled="saving"><option value="ar">{{ $t('language.ar') }}</option><option value="en">{{ $t('language.en') }}</option></select></label><label class="checkbox-label setting-toggle"><input v-model="form.is_active" type="checkbox" :disabled="saving">{{ $t('common.active') }}</label></div></section>
        <section class="card settings-section"><div><h2>{{ t('restaurant.contact') }}</h2><p>{{ t('restaurant.contactHelp') }}</p></div><div class="settings-grid"><label>WhatsApp<input v-model="form.whatsapp" type="tel" maxlength="30" placeholder="+970591234567" :disabled="saving"><span v-if="errorFor('whatsapp')" class="field-error">{{ errorFor('whatsapp') }}</span></label><label>{{ t('restaurant.phone') }}<input v-model="form.phone" type="tel" maxlength="30" placeholder="0591234567" :disabled="saving"><span v-if="errorFor('phone')" class="field-error">{{ errorFor('phone') }}</span></label><label class="full">{{ t('restaurant.address') }}<textarea v-model="form.address" rows="3" maxlength="2000" :disabled="saving" /><span v-if="errorFor('address')" class="field-error">{{ errorFor('address') }}</span></label></div></section>
        <section class="card settings-section"><div><h2>{{ t('restaurant.brand') }}</h2><p>{{ t('restaurant.brandHelp') }}</p></div><div class="settings-grid"><CurrencySelect v-model="form.currency" :disabled="saving" /><ThemeSelector v-model="form.theme_key" v-model:primary-color="form.primary_color" :disabled="saving" /></div><div class="brand-images"><RestaurantImageField ref="logoField" :label="t('restaurant.logo')" profile="logo" :guidance="t('restaurant.logoGuidance')" :current-url="restaurant?.logo_url" :busy="imageBusy.logo" @upload="handleImage('logo', $event)" @remove="removeImage('logo')" /><RestaurantImageField ref="coverField" :label="t('restaurant.cover')" profile="cover" :guidance="t('restaurant.coverGuidance')" :current-url="restaurant?.cover_image_url" :busy="imageBusy.cover" @upload="handleImage('cover', $event)" @remove="removeImage('cover')" /></div></section>
        <section class="card settings-section"><div><h2>{{ t('restaurant.hours') }}</h2><p>{{ t('restaurant.hoursHelp') }}</p></div><div class="hours-list"><div v-for="(day, index) in days" :key="day" class="hours-row"><strong>{{ t(`days.${day}`) }}</strong><label class="checkbox-label"><input v-model="form.opening_hours[day].is_open" type="checkbox" :disabled="saving" @change="toggleDay(day)">{{ t('restaurant.open') }}</label><label>{{ t('restaurant.opens') }}<input v-model="form.opening_hours[day].open" type="time" :disabled="saving || !form.opening_hours[day].is_open"><span v-if="errorFor(`opening_hours.${day}.open`)" class="field-error">{{ errorFor(`opening_hours.${day}.open`) }}</span></label><label>{{ t('restaurant.closes') }}<input v-model="form.opening_hours[day].close" type="time" :disabled="saving || !form.opening_hours[day].is_open"><span v-if="errorFor(`opening_hours.${day}.close`)" class="field-error">{{ errorFor(`opening_hours.${day}.close`) }}</span></label><button type="button" class="text-button" :disabled="saving" @click="setTwentyFourHours(form.opening_hours[day])">{{ t('restaurant.open24') }}</button><button v-if="index" type="button" class="text-button" :disabled="saving" @click="copyHours(form.opening_hours[days[index - 1]], form.opening_hours[day])">{{ t('restaurant.copyPrevious') }}</button><button type="button" class="text-button" :disabled="saving" @click="copyToAll(day)">{{ t('restaurant.copyAll') }}</button></div></div></section>
        <BaseButton class="settings-save" type="submit" :loading="saving">{{ t('restaurant.saveSettings') }}</BaseButton>
      </form>
    </template>
  </section>
</template>
