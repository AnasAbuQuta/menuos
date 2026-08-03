<script setup>
import { computed, onBeforeUnmount, reactive, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import { apiError } from '../services/api'
import { uploadCover, uploadLogo } from '../services/restaurant'
import BaseAlert from '../components/ui/BaseAlert.vue'
import CurrencySelect from '../components/CurrencySelect.vue'
import ThemeSelector from '../components/ThemeSelector.vue'
import ImageEditorModal from '../components/ImageEditorModal.vue'
import { validateImageFile } from '../utils/imageEditor'
import { DAYS, copyHours, defaultOpeningHours, setTwentyFourHours } from '../utils/openingHours'

const auth = useAuthStore()
const router = useRouter()
const steps = ['Restaurant', 'Language', 'Contact', 'Brand images', 'Theme', 'Opening hours', 'Review']
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
const form = reactive({ ...structuredClone(defaults), ...saved, opening_hours: { ...defaultOpeningHours(), ...(saved.opening_hours || {}) } })

watch(form, (value) => localStorage.setItem(draftKey.value, JSON.stringify(value)), { deep: true })
const name = computed(() => form.name_en.trim() || form.name_ar.trim() || 'Your restaurant')

function validateStep() {
  error.value = ''
  if (step.value === 0 && !form.name_ar.trim() && !form.name_en.trim()) error.value = 'Add a restaurant name in Arabic or English.'
  if (step.value === 2 && form.whatsapp && !/^\+?[0-9 ()-]{6,30}$/.test(form.whatsapp)) error.value = 'Enter a valid WhatsApp number.'
  return !error.value
}
function next() { if (validateStep() && step.value < steps.length - 1) step.value++ }
function back() { error.value = ''; if (step.value > 0) step.value-- }
function toggleDay(day) { const hours = form.opening_hours[day]; if (hours.is_open) { hours.open ||= '09:00'; hours.close ||= '23:00' } else { hours.open = null; hours.close = null } }
function copyAll(day) { DAYS.forEach((target) => copyHours(form.opening_hours[day], form.opening_hours[target])) }

function chooseImage(kind, event) {
  const file = event.target.files?.[0]
  event.target.value = ''
  if (!file) return
  const issue = validateImageFile(file)
  if (issue) { error.value = issue; return }
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
  } catch (exception) { error.value = apiError(exception, 'Restaurant setup could not be completed. Your draft is safe.') }
  finally { loading.value = false }
}

onBeforeUnmount(() => Object.values(previews).forEach((url) => url && URL.revokeObjectURL(url)))
</script>

<template>
  <section class="setup-page setup-wizard">
    <header class="page-heading"><div><p class="eyebrow">Guided setup</p><h1>Create your restaurant</h1><p>Step {{ step + 1 }} of {{ steps.length }} · Your progress is saved on this device.</p></div></header>
    <ol class="wizard-progress"><li v-for="(label, index) in steps" :key="label" :class="{ active: index === step, complete: index < step }"><span>{{ index + 1 }}</span><small>{{ label }}</small></li></ol>
    <BaseAlert v-if="error" type="error">{{ error }}</BaseAlert>
    <form class="card wizard-card" @submit.prevent="step === steps.length - 1 ? submit() : next()">
      <section v-if="step === 0"><h2>Restaurant basics</h2><p>Use at least one language. You can complete translations later.</p><div class="settings-grid"><label>Arabic name<input v-model="form.name_ar" maxlength="255" dir="rtl"></label><label>English name<input v-model="form.name_en" maxlength="255"></label><label class="full">Arabic description<textarea v-model="form.description_ar" rows="3" maxlength="5000" dir="rtl" /></label><label class="full">English description<textarea v-model="form.description_en" rows="3" maxlength="5000" /></label></div></section>
      <section v-else-if="step === 1"><h2>Language and currency</h2><div class="settings-grid"><label>Default menu language<select v-model="form.default_language"><option value="ar">Arabic</option><option value="en">English</option></select></label><CurrencySelect v-model="form.currency" /></div></section>
      <section v-else-if="step === 2"><h2>Contact and location</h2><div class="settings-grid"><label>WhatsApp<input v-model="form.whatsapp" type="tel" maxlength="30"></label><label>Phone<input v-model="form.phone" type="tel" maxlength="30"></label><label class="full">Address<textarea v-model="form.address" rows="3" maxlength="2000" /></label></div></section>
      <section v-else-if="step === 3"><h2>Brand images</h2><p>Crop and optimize images before upload. This step is optional.</p><div class="wizard-images"><label v-for="kind in ['logo', 'cover']" :key="kind" class="wizard-image"><strong>{{ kind === 'logo' ? 'Logo (1:1)' : 'Cover (16:9 or 3:1)' }}</strong><img v-if="previews[kind]" :src="previews[kind]" :alt="`${kind} preview`"><span v-else>No image selected</span><input type="file" accept="image/jpeg,image/png,image/webp" @change="chooseImage(kind, $event)"></label></div></section>
      <section v-else-if="step === 4"><h2>Menu theme</h2><p>Choose a starting point and personalize the main color.</p><ThemeSelector v-model="form.theme_key" v-model:primary-color="form.primary_color" /></section>
      <section v-else-if="step === 5"><h2>Opening hours</h2><div class="hours-list"><div v-for="(day, index) in DAYS" :key="day" class="hours-row"><strong>{{ day[0].toUpperCase() + day.slice(1) }}</strong><label class="checkbox-label"><input v-model="form.opening_hours[day].is_open" type="checkbox" @change="toggleDay(day)">Open</label><template v-if="form.opening_hours[day].is_open"><input v-model="form.opening_hours[day].open" type="time" aria-label="Opening time"><input v-model="form.opening_hours[day].close" type="time" aria-label="Closing time"><button type="button" class="text-button" @click="setTwentyFourHours(form.opening_hours[day])">24 hours</button><button v-if="index" type="button" class="text-button" @click="copyHours(form.opening_hours[DAYS[index - 1]], form.opening_hours[day])">Copy previous</button><button type="button" class="text-button" @click="copyAll(day)">Copy to all</button></template></div></div></section>
      <section v-else><h2>Review and create</h2><div class="review-grid"><div><small>Restaurant</small><strong>{{ name }}</strong></div><div><small>Language / currency</small><strong>{{ form.default_language.toUpperCase() }} · {{ form.currency }}</strong></div><div><small>Theme</small><strong>{{ form.theme_key }}</strong></div><div><small>Brand images</small><strong>{{ [images.logo && 'Logo', images.cover && 'Cover'].filter(Boolean).join(' + ') || 'None selected' }}</strong></div></div><p>You can edit every setting after setup.</p></section>
      <footer class="wizard-actions"><button v-if="step" class="button button-secondary" type="button" :disabled="loading" @click="back">Back</button><button class="button" type="submit" :disabled="loading">{{ loading ? 'Creating…' : step === steps.length - 1 ? 'Create restaurant' : 'Continue' }}</button></footer>
    </form>
    <ImageEditorModal :open="Boolean(sourceImage)" :file="sourceImage" :profile="imageKind || 'logo'" @confirm="acceptImage" @close="sourceImage = null; imageKind = ''" />
  </section>
</template>
