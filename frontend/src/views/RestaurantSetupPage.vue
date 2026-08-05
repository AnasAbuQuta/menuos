<script setup>
import { computed, onBeforeUnmount, reactive, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '../stores/auth'
import { apiError } from '../services/api'
import { uploadCover, uploadLogo } from '../services/restaurant'
import BaseAlert from '../components/ui/BaseAlert.vue'
import CurrencySelect from '../components/CurrencySelect.vue'
import ThemeSelector from '../components/ThemeSelector.vue'
import ImageEditorModal from '../components/ImageEditorModal.vue'
import { validateImageFile } from '../utils/imageEditor'
import { DAYS, copyHours, defaultOpeningHours, setTwentyFourHours } from '../utils/openingHours'
import { useLocalizedMeta } from '../composables/useLocalizedMeta'

const auth = useAuthStore()
const router = useRouter()
const { locale, t } = useI18n()
useLocalizedMeta('meta.setup')
const steps = computed(() => ['basics', 'languageCurrency', 'contactLocation', 'brandImages', 'menuTheme', 'openingHours', 'review'].map((key) => t(`setup.${key}`)))
const step = ref(0)
const error = ref('')
const loading = ref(false)
const imageKind = ref('')
const sourceImage = ref(null)
const images = reactive({ logo: null, cover: null })
const previews = reactive({ logo: '', cover: '' })
const draftKey = computed(() => `menuos_setup_draft:${auth.user?.id || 'guest'}`)
const defaults = { name_ar: '', name_en: '', description_ar: '', description_en: '', default_language: 'ar', whatsapp: '', phone: '', address: '', currency: 'ILS', theme_key: 'modern', primary_color: '', opening_hours: defaultOpeningHours() }
function readDraft() {
  try { return JSON.parse(localStorage.getItem(draftKey.value) || '{}') }
  catch { return {} }
}
const saved = readDraft()
const draftRestored = ref(Object.keys(saved).length > 0)
const form = reactive({ ...structuredClone(defaults), ...saved, opening_hours: { ...defaultOpeningHours(), ...(saved.opening_hours || {}) } })

watch(form, (value) => localStorage.setItem(draftKey.value, JSON.stringify(value)), { deep: true })
const name = computed(() => (locale.value === 'ar' ? form.name_ar.trim() || form.name_en.trim() : form.name_en.trim() || form.name_ar.trim()) || t('setup.yourRestaurant'))

function validateStep() {
  error.value = ''
  if (step.value === 0 && !form.name_ar.trim() && !form.name_en.trim()) error.value = t('setup.nameRequired')
  if (step.value === 2 && form.whatsapp && !/^\+?[0-9 ()-]{6,30}$/.test(form.whatsapp)) error.value = t('setup.whatsappInvalid')
  return !error.value
}
function next() { if (validateStep() && step.value < steps.value.length - 1) step.value++ }
function back() { error.value = ''; if (step.value > 0) step.value-- }
function toggleDay(day) { const hours = form.opening_hours[day]; if (hours.is_open) { hours.open ||= '09:00'; hours.close ||= '23:00' } else { hours.open = null; hours.close = null } }
function copyAll(day) { DAYS.forEach((target) => copyHours(form.opening_hours[day], form.opening_hours[target])) }

function chooseImage(kind, event) {
  const file = event.target.files?.[0]
  event.target.value = ''
  if (!file) return
  const issue = validateImageFile(file)
  if (issue) { error.value = t(`forms.${issue}`); return }
  imageKind.value = kind; sourceImage.value = file
}
function acceptImage(file) {
  const kind = imageKind.value
  if (previews[kind]) URL.revokeObjectURL(previews[kind])
  images[kind] = file; previews[kind] = URL.createObjectURL(file)
  imageKind.value = ''; sourceImage.value = null
}

async function submit() {
  if (loading.value || !validateStep()) return
  loading.value = true; error.value = ''
  try {
    const payload = structuredClone(form)
    if (!payload.primary_color) payload.primary_color = null
    await auth.createRestaurant(payload)
    if (images.logo) auth.user.restaurant = await uploadLogo(images.logo)
    if (images.cover) auth.user.restaurant = await uploadCover(images.cover)
    localStorage.removeItem(draftKey.value)
    await router.push({ name: 'dashboard' })
  } catch (exception) { error.value = apiError(exception, t('setup.submitError')) }
  finally { loading.value = false }
}

function discardDraft() {
  localStorage.removeItem(draftKey.value)
  Object.assign(form, structuredClone(defaults))
  draftRestored.value = false
  step.value = 0
}

onBeforeUnmount(() => Object.values(previews).forEach((url) => url && URL.revokeObjectURL(url)))
</script>

<template>
  <section class="setup-page setup-wizard">
    <header class="page-heading"><div><p class="eyebrow">{{ t('setup.guided') }}</p><h1>{{ t('setup.createTitle') }}</h1><p>{{ t('setup.stepOf', { current: step + 1, total: steps.length }) }}</p></div></header>
    <ol class="wizard-progress"><li v-for="(label, index) in steps" :key="label" :class="{ active: index === step, complete: index < step }"><span>{{ index + 1 }}</span><small>{{ label }}</small></li></ol>
    <BaseAlert v-if="error" type="error">{{ error }}</BaseAlert>
    <BaseAlert v-if="draftRestored" type="info">{{ t('setup.draftRestored') }} <button type="button" class="text-button" @click="discardDraft">{{ t('setup.discardDraft') }}</button></BaseAlert>
    <form class="card wizard-card" @submit.prevent="step === steps.length - 1 ? submit() : next()">
      <section v-if="step === 0"><h2>{{ t('setup.basics') }}</h2><p>{{ t('setup.basicsHelp') }}</p><div class="settings-grid"><label>{{ t('restaurant.nameAr') }}<input v-model="form.name_ar" maxlength="255" dir="rtl"></label><label>{{ t('restaurant.nameEn') }}<input v-model="form.name_en" maxlength="255"></label><label class="full">{{ t('restaurant.descriptionAr') }}<textarea v-model="form.description_ar" rows="3" maxlength="5000" dir="rtl" /></label><label class="full">{{ t('restaurant.descriptionEn') }}<textarea v-model="form.description_en" rows="3" maxlength="5000" /></label></div></section>
      <section v-else-if="step === 1"><h2>{{ t('setup.languageCurrency') }}</h2><div class="settings-grid"><label>{{ t('setup.defaultLanguage') }}<select v-model="form.default_language"><option value="ar">{{ t('language.ar') }}</option><option value="en">{{ t('language.en') }}</option></select></label><CurrencySelect v-model="form.currency" /></div></section>
      <section v-else-if="step === 2"><h2>{{ t('setup.contactLocation') }}</h2><div class="settings-grid"><label>WhatsApp<input v-model="form.whatsapp" type="tel" maxlength="30"></label><label>{{ t('restaurant.phone') }}<input v-model="form.phone" type="tel" maxlength="30"></label><label class="full">{{ t('restaurant.address') }}<textarea v-model="form.address" rows="3" maxlength="2000" /></label></div></section>
      <section v-else-if="step === 3"><h2>{{ t('setup.brandImages') }}</h2><p>{{ t('setup.brandImagesHelp') }}</p><div class="wizard-images"><label v-for="kind in ['logo', 'cover']" :key="kind" class="wizard-image"><strong>{{ t(kind === 'logo' ? 'setup.logoRatio' : 'setup.coverRatio') }}</strong><img v-if="previews[kind]" :src="previews[kind]" :alt="t('setup.imagePreview', { type: t(`imageEditor.${kind}`) })"><span v-else>{{ t('setup.noImageSelected') }}</span><input type="file" accept="image/jpeg,image/png,image/webp" @change="chooseImage(kind, $event)"></label></div></section>
      <section v-else-if="step === 4"><h2>{{ t('setup.menuTheme') }}</h2><p>{{ t('setup.menuThemeHelp') }}</p><ThemeSelector v-model="form.theme_key" v-model:primary-color="form.primary_color" /></section>
      <section v-else-if="step === 5"><h2>{{ t('setup.openingHours') }}</h2><div class="hours-list"><div v-for="(day, index) in DAYS" :key="day" class="hours-row"><strong>{{ t(`days.${day}`) }}</strong><label class="checkbox-label"><input v-model="form.opening_hours[day].is_open" type="checkbox" @change="toggleDay(day)">{{ t('restaurant.open') }}</label><template v-if="form.opening_hours[day].is_open"><input v-model="form.opening_hours[day].open" type="time" :aria-label="t('setup.openingTime')"><input v-model="form.opening_hours[day].close" type="time" :aria-label="t('setup.closingTime')"><button type="button" class="text-button" @click="setTwentyFourHours(form.opening_hours[day])">{{ t('restaurant.open24') }}</button><button v-if="index" type="button" class="text-button" @click="copyHours(form.opening_hours[DAYS[index - 1]], form.opening_hours[day])">{{ t('restaurant.copyPrevious') }}</button><button type="button" class="text-button" @click="copyAll(day)">{{ t('restaurant.copyAll') }}</button></template></div></div></section>
      <section v-else><h2>{{ t('setup.review') }}</h2><div class="review-grid"><div><small>{{ t('setup.summaryRestaurant') }}</small><strong>{{ name }}</strong></div><div><small>{{ t('setup.summaryLanguage') }}</small><strong>{{ t(`language.${form.default_language}`) }} · {{ form.currency }}</strong></div><div><small>{{ t('setup.summaryTheme') }}</small><strong>{{ t(`themes.${form.theme_key}`) }}</strong></div><div><small>{{ t('setup.summaryImages') }}</small><strong>{{ [images.logo && t('imageEditor.logo'), images.cover && t('imageEditor.cover')].filter(Boolean).join(' + ') || t('setup.noneSelected') }}</strong></div></div><p>{{ t('setup.editLater') }}</p></section>
      <footer class="wizard-actions"><button v-if="step" class="button button-secondary" type="button" :disabled="loading" @click="back">{{ t('setup.back') }}</button><button class="button" type="submit" :disabled="loading">{{ loading ? t('setup.creating') : step === steps.length - 1 ? t('setup.create') : t('setup.next') }}</button></footer>
    </form>
    <ImageEditorModal :open="Boolean(sourceImage)" :file="sourceImage" :profile="imageKind || 'logo'" @confirm="acceptImage" @close="sourceImage = null; imageKind = ''" />
  </section>
</template>
