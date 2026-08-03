<script setup>
import { ref, watch } from 'vue'
const props = defineProps({ src: { type: String, default: '' }, alt: { type: String, required: true }, lazy: { type: Boolean, default: true } })
const loaded = ref(false)
const failed = ref(false)
watch(() => props.src, () => { loaded.value = false; failed.value = false })
</script>
<template><div class="app-image" :class="{ loaded, failed }"><div v-if="!loaded && !failed" class="app-image-placeholder" aria-hidden="true"></div><img v-if="src && !failed" :src="src" :alt="alt" :loading="lazy ? 'lazy' : 'eager'" @load="loaded = true" @error="failed = true"><div v-if="!src || failed" class="app-image-fallback" role="img" :aria-label="`${alt} unavailable`">◇</div></div></template>
