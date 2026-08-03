<script setup>
import { computed } from 'vue'
import { themeFor, themeKeys, themeVariables } from '../theme/restaurantThemes'
const props = defineProps({ modelValue: { type: String, default: 'modern' }, primaryColor: { type: String, default: '' }, disabled: Boolean })
const emit = defineEmits(['update:modelValue', 'update:primaryColor'])
const previewStyle = computed(() => themeVariables(props.modelValue, props.primaryColor || null))
function select(key) { emit('update:modelValue', key); emit('update:primaryColor', '') }
function reset() { emit('update:primaryColor', '') }
</script>
<template><div class="theme-selector"><div class="theme-options" role="radiogroup" :aria-label="$t('themes.choose')"><button v-for="key in themeKeys" :key="key" class="theme-card" :class="{ selected: modelValue === key }" type="button" role="radio" :aria-checked="modelValue === key" :disabled="disabled" @click="select(key)"><span class="theme-swatch" :style="themeVariables(key)"><i /><i /><i /></span><strong>{{ $t(`themes.${key}`) }}</strong></button></div><div class="theme-live-preview" :style="previewStyle"><span>{{ $t('themes.preview') }}</span><strong>MenuOS</strong><button type="button">{{ $t('common.save') }}</button></div><div class="color-override"><label>{{ $t('themes.customColor') }}<input :value="primaryColor || themeFor(modelValue).primary" type="color" :disabled="disabled" @input="$emit('update:primaryColor', $event.target.value)"></label><button class="button button-secondary" type="button" :disabled="disabled || !primaryColor" @click="reset">{{ $t('themes.reset') }}</button></div></div></template>
