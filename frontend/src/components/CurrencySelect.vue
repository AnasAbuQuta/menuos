<script setup>
defineProps({ modelValue: { type: String, default: 'ILS' }, disabled: Boolean })
defineEmits(['update:modelValue'])
import { currencyCodes } from '../constants/currencies'
</script>
<template><label>{{ $t('restaurant.currency') }}<select :value="modelValue" :disabled="disabled" @change="$emit('update:modelValue', $event.target.value)"><option v-for="code in currencyCodes" :key="code" :value="code">{{ $t(`currencies.${code}`) }}</option></select></label></template>
