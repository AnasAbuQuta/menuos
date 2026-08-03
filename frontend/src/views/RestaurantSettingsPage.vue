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
const form = reactive({ name_ar: '', name_en: '', description_ar: '', description_en: '', default_language: 'ar', is_active: true, whatsapp: '', phone: '', address: '', currency: 'ILS', primary_color: '#176B52', opening_hours: {} })
const colorValid = computed(() => /^#[0-9A-Fa-f]{6}$/.test(form.primary_color))
const { t } = useI18n()

function defaultHours() {
  return Object.fromEntries(days.map((day) => [day, { is_open: day !== 'friday', open: day === 'friday' ? null : '09:00', close: day === 'friday' ? null : '23:00' }]))
}

function populate(data) {
  restaurant.value = data
  Object.assign(form, {
    name_ar: data.name_ar ?? data.name ?? '', name_en: data.name_en ?? '', description_ar: data.description_ar ?? data.description ?? '', description_en: data.description_en ?? '', default_language: data.default_language ?? 'ar', is_active: data.is_active,
    whatsapp: data.whatsapp ?? '', phone: data.phone ?? '', address: data.address ?? '',
    currency: data.currency ?? 'ILS', primary_color: data.primary_color ?? '#176B52',
    opening_hours: data.opening_hours ?? defaultHours(),
  })
  auth.user.restaurant = data
}

async function load() {
  loading.value = true; pageError.value = ''
  try { populate(await getRestaurant()) }
  catch (error) { pageError.value = apiError(error, 'Unable to load restaurant settings.') }
  finally { loading.value = false }
}

async function save() {
  if (saving.value) return
  errors.value = {}
  if (!form.name_ar.trim() && !form.name_en.trim()) { errors.value = { name_ar: [t('forms.itemRequired')] }; await focusFirstError(); return }
  if (!colorValid.value) { errors.value = { primary_color: ['Use a six-digit hexadecimal color such as #E63946.'] }; await focusFirstError(); return }
  saving.value = true
  try {
    const payload = JSON.parse(JSON.stringify(form))
    payload.name_ar = payload.name_ar.trim() || null
    payload.name_en = payload.name_en.trim() || null
    payload.primary_color = payload.primary_color.toUpperCase()
    populate(await updateRestaurant(payload))
    toast.success('Restaurant settings saved successfully.')
  } catch (error) { errors.value = error.response?.data?.errors ?? { general: [apiError(error, 'Unable to save settings.')] }; await focusFirstError() }
  finally { saving.value = false }
}

function toggleDay(day) {
  const hours = form.opening_hours[day]
  if (!hours.is_open) { hours.open = null; hours.close = null }
  else { hours.open ||= '09:00'; hours.close ||= '23:00' }
}

async function handleImage(type, file) {
  imageBusy[type] = true; imageProgress[type] = 0; pageError.value = ''
  try {
    const progress = (value) => { imageProgress[type] = value }
    const updated = type === 'logo' ? await uploadLogo(file, progress) : await uploadCover(file, progress)
    populate(updated)
    if (type === 'logo') logoField.value?.reset(); else coverField.value?.reset()
    toast.success(`Restaurant ${type} updated successfully.`)
  } catch (error) { pageError.value = apiError(error, `Unable to upload ${type}.`) }
  finally { imageBusy[type] = false }
}

async function removeImage(type) {
  imageBusy[type] = true; pageError.value = ''
  try { populate(type === 'logo' ? await deleteLogo() : await deleteCover()); toast.success(`Restaurant ${type} removed.`) }
  catch (error) { pageError.value = apiError(error, `Unable to remove ${type}.`) }
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
    <div><p class="eyebrow">Restaurant profile</p><h1>Restaurant Settings</h1><p>Manage the information and branding that will power your future public menu.</p></div>
    <BaseLoading v-if="loading" :rows="6" label="Loading restaurant settings" />
    <div v-else-if="pageError && !restaurant" class="error-state" role="alert"><p>{{ pageError }}</p><button class="button button-secondary" type="button" @click="load">Try again</button></div>
    <template v-else>
      <p v-if="pageError" class="error" role="alert">{{ pageError }}</p>
      <p v-if="errors.general" class="error" role="alert">{{ errors.general[0] }}</p>
      <form class="settings-form" @submit.prevent="save">
        <section class="card settings-section"><div><h2>{{ $t('restaurant.basic') }}</h2><p>{{ $t('restaurant.settingsIntro') }}</p></div><div class="settings-grid"><label>{{ $t('restaurant.nameAr') }}<input v-model="form.name_ar" maxlength="255" :disabled="saving"><span v-if="errorFor('name_ar')" class="field-error">{{ errorFor('name_ar') }}</span></label><label>{{ $t('restaurant.nameEn') }}<input v-model="form.name_en" maxlength="255" :disabled="saving"><span v-if="errorFor('name_en')" class="field-error">{{ errorFor('name_en') }}</span></label><label class="full">{{ $t('restaurant.descriptionAr') }}<textarea v-model="form.description_ar" rows="4" maxlength="5000" :disabled="saving" /></label><label class="full">{{ $t('restaurant.descriptionEn') }}<textarea v-model="form.description_en" rows="4" maxlength="5000" :disabled="saving" /></label><label>{{ $t('restaurant.defaultLanguage') }}<select v-model="form.default_language" :disabled="saving"><option value="ar">{{ $t('language.ar') }}</option><option value="en">{{ $t('language.en') }}</option></select></label><label class="checkbox-label setting-toggle"><input v-model="form.is_active" type="checkbox" :disabled="saving">{{ $t('common.active') }}</label></div></section>
        <section class="card settings-section"><div><h2>Contact Information</h2><p>Country codes are preserved but never guessed automatically.</p></div><div class="settings-grid"><label>WhatsApp<input v-model="form.whatsapp" type="tel" maxlength="30" placeholder="+970591234567" :disabled="saving"><span v-if="errorFor('whatsapp')" class="field-error">{{ errorFor('whatsapp') }}</span></label><label>Phone<input v-model="form.phone" type="tel" maxlength="30" placeholder="0591234567" :disabled="saving"><span v-if="errorFor('phone')" class="field-error">{{ errorFor('phone') }}</span></label><label class="full">Address<textarea v-model="form.address" rows="3" maxlength="2000" :disabled="saving" /><span v-if="errorFor('address')" class="field-error">{{ errorFor('address') }}</span></label></div></section>
        <section class="card settings-section"><div><h2>Brand Settings</h2><p>These values will be used by the future public menu.</p></div><div class="settings-grid"><label>Currency<select v-model="form.currency" :disabled="saving"><option value="ILS">ILS</option><option value="USD">USD</option><option value="JOD">JOD</option></select><span v-if="errorFor('currency')" class="field-error">{{ errorFor('currency') }}</span></label><label>Primary color<div class="color-control"><input v-model="form.primary_color" type="color" :disabled="saving"><input v-model.trim="form.primary_color" maxlength="7" placeholder="#E63946" :disabled="saving"></div><span v-if="errorFor('primary_color')" class="field-error">{{ errorFor('primary_color') }}</span></label></div><div class="brand-images"><RestaurantImageField ref="logoField" label="Logo" guidance="A square image is preferred. JPG, PNG, or WebP up to 2 MB." :current-url="restaurant?.logo_url" :busy="imageBusy.logo" @upload="handleImage('logo', $event)" @remove="removeImage('logo')" /><RestaurantImageField ref="coverField" label="Cover" guidance="A wide landscape image is preferred. JPG, PNG, or WebP up to 2 MB." :current-url="restaurant?.cover_image_url" :busy="imageBusy.cover" @upload="handleImage('cover', $event)" @remove="removeImage('cover')" /></div></section>
        <section class="card settings-section"><div><h2>Opening Hours</h2><p>Configure one daily shift. Overnight hours are not supported yet.</p></div><div class="hours-list"><div v-for="day in days" :key="day" class="hours-row"><strong>{{ day.charAt(0).toUpperCase() + day.slice(1) }}</strong><label class="checkbox-label"><input v-model="form.opening_hours[day].is_open" type="checkbox" :disabled="saving" @change="toggleDay(day)">Open</label><label>Opens<input v-model="form.opening_hours[day].open" type="time" :disabled="saving || !form.opening_hours[day].is_open"><span v-if="errorFor(`opening_hours.${day}.open`)" class="field-error">{{ errorFor(`opening_hours.${day}.open`) }}</span></label><label>Closes<input v-model="form.opening_hours[day].close" type="time" :disabled="saving || !form.opening_hours[day].is_open"><span v-if="errorFor(`opening_hours.${day}.close`)" class="field-error">{{ errorFor(`opening_hours.${day}.close`) }}</span></label></div></div></section>
        <BaseButton class="settings-save" type="submit" :loading="saving">Save settings</BaseButton>
      </form>
    </template>
  </section>
</template>
