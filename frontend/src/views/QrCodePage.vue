<script setup>
import { onMounted, ref } from 'vue'
import { apiError } from '../services/api'
import { getRestaurantQrCode } from '../services/restaurantQr'

const data = ref(null)
const loading = ref(true)
const error = ref('')
const copied = ref(false)

async function load() {
  loading.value = true
  error.value = ''
  try { data.value = await getRestaurantQrCode() }
  catch (requestError) { error.value = apiError(requestError, 'Unable to generate the QR code.') }
  finally { loading.value = false }
}

async function copyUrl() {
  try {
    await navigator.clipboard.writeText(data.value.public_menu_url)
    copied.value = true
    window.setTimeout(() => { copied.value = false }, 2000)
  } catch { error.value = 'Copy failed. Select and copy the URL manually.' }
}

function download() {
  const link = document.createElement('a')
  link.href = data.value.qr_code
  link.download = 'menuos-restaurant-menu-qr.png'
  link.click()
}

onMounted(load)
</script>

<template>
  <section class="qr-page">
    <div class="page-heading"><div><span class="eyebrow">Public menu</span><h1>QR Code</h1><p>Let customers scan directly to your public menu.</p></div></div>
    <div v-if="loading" class="card state-card" aria-live="polite">Generating QR code…</div>
    <div v-else-if="error && !data" class="card state-card" role="alert"><p class="error">{{ error }}</p><button class="button" @click="load">Try again</button></div>
    <div v-else class="card qr-card">
      <img :src="data.qr_code" alt="QR code for the restaurant's public menu" width="360" height="360">
      <label>Public menu URL<input :value="data.public_menu_url" readonly @focus="$event.target.select()"></label>
      <p v-if="copied" class="success" role="status">URL copied.</p><p v-else-if="error" class="error" role="alert">{{ error }}</p>
      <div class="form-actions">
        <button class="button" type="button" @click="copyUrl">Copy URL</button>
        <button class="button button-secondary" type="button" @click="download">Download PNG</button>
        <a class="button button-secondary" :href="data.public_menu_url" target="_blank" rel="noopener noreferrer">Open public menu</a>
      </div>
    </div>
  </section>
</template>
