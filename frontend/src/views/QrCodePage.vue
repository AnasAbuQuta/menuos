<script setup>
import { onMounted, ref } from 'vue'
import { apiError } from '../services/api'
import { getRestaurantQrCode } from '../services/restaurantQr'
import BaseLoading from '../components/ui/BaseLoading.vue'
import BaseAlert from '../components/ui/BaseAlert.vue'
import { useToastStore } from '../stores/toast'
import AppIcon from '../components/ui/AppIcon.vue'
import { useI18n } from 'vue-i18n'
import { useLocalizedMeta } from '../composables/useLocalizedMeta'

const data = ref(null)
const loading = ref(true)
const error = ref('')
const copied = ref(false)
const toast = useToastStore()
const { t } = useI18n()
useLocalizedMeta('meta.qr')

async function load() {
  loading.value = true
  error.value = ''
  try { data.value = await getRestaurantQrCode() }
  catch (requestError) { error.value = apiError(requestError, t('qr.error')) }
  finally { loading.value = false }
}

async function copyUrl() {
  try {
    await navigator.clipboard.writeText(data.value.public_menu_url)
    copied.value = true
    toast.success(t('qr.copied'))
    window.setTimeout(() => { copied.value = false }, 2000)
  } catch { error.value = t('management.qr.copyFailed'); toast.error(error.value) }
}

function download() {
  const link = document.createElement('a')
  link.href = data.value.qr_code
  link.download = 'menuos-restaurant-menu-qr.png'
  link.click()
  toast.success(t('management.qr.downloaded'))
}

onMounted(load)
</script>

<template>
  <section class="qr-page">
    <div class="page-heading"><div><span class="eyebrow">{{ t('qr.eyebrow') }}</span><h1>{{ t('qr.title') }}</h1><p>{{ t('qr.intro') }}</p></div></div>
    <BaseLoading v-if="loading" :rows="4" :label="t('qr.generating')" />
    <div v-else-if="error && !data" class="card state-card" role="alert"><p class="error">{{ error }}</p><button class="button" @click="load">{{ t('qr.retry') }}</button></div>
    <div v-else class="card qr-card">
      <img :src="data.qr_code" :alt="t('qr.alt')" width="360" height="360">
      <label>{{ t('qr.url') }}<input :value="data.public_menu_url" readonly @focus="$event.target.select()"></label>
      <BaseAlert v-if="copied" type="success">{{ t('qr.copied') }}</BaseAlert><BaseAlert v-else-if="error" type="error">{{ error }}</BaseAlert>
      <div class="form-actions">
        <button class="button" type="button" :title="$t('qr.copy')" @click="copyUrl"><AppIcon name="copy" :size="18" />{{ $t('qr.copy') }}</button>
        <button class="button button-secondary" type="button" :title="$t('qr.download')" @click="download"><AppIcon name="download" :size="18" />{{ $t('qr.download') }}</button>
        <a class="button button-secondary" :href="data.public_menu_url" target="_blank" rel="noopener noreferrer" :title="$t('qr.open')"><AppIcon name="external" :size="18" />{{ $t('qr.open') }}</a>
      </div>
    </div>
  </section>
</template>
