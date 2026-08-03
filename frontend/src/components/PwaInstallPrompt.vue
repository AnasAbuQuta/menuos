<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue'
const prompt = ref(null)
const dismissed = ref(sessionStorage.getItem('menuos_install_dismissed') === '1')
function available(event) { event.preventDefault(); prompt.value = event }
async function install() { await prompt.value?.prompt(); prompt.value = null }
function dismiss() { dismissed.value = true; sessionStorage.setItem('menuos_install_dismissed', '1') }
onMounted(() => window.addEventListener('beforeinstallprompt', available))
onBeforeUnmount(() => window.removeEventListener('beforeinstallprompt', available))
</script>
<template><aside v-if="prompt && !dismissed" class="pwa-prompt" role="status"><img src="/pwa-icon.svg" alt="" width="44" height="44"><div><strong>Install MenuOS</strong><span>Open your restaurant workspace faster.</span></div><button class="button" @click="install">Install</button><button class="pwa-dismiss" aria-label="Dismiss install prompt" @click="dismiss">×</button></aside></template>
