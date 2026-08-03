<script setup>
import BaseSpinner from './BaseSpinner.vue'
defineProps({ variant: { type: String, default: 'primary' }, loading: Boolean, disabled: Boolean, type: { type: String, default: 'button' } })
</script>
<template><button class="ui-button" :class="`ui-button-${variant}`" :type="type" :disabled="disabled || loading" :aria-busy="loading"><BaseSpinner v-if="loading" size="sm" /><slot /></button></template>
