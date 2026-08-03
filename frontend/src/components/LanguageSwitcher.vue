<script setup>
import { useI18n } from 'vue-i18n'
import { setLocale } from '../i18n'
const { locale, t } = useI18n()
const emit = defineEmits(['change'])
function choose(code) { setLocale(code); emit('change', code) }
</script>
<template><div class="language-switcher" role="group" :aria-label="t('language.label')"><button v-for="code in ['ar', 'en']" :key="code" type="button" :class="{ active: locale === code }" :aria-pressed="locale === code" @click="choose(code)">{{ t(`language.${code}`) }}</button></div></template>
