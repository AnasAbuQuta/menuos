<script setup>defineProps({ rows: { type: Number, default: 3 }, label: { type: String, default: '' } })</script>
<template><div class="ui-loading" role="status" :aria-label="label || $t('common.loading')"><div v-for="row in rows" :key="row" class="ui-skeleton" :style="{ '--skeleton-width': `${100 - ((row - 1) % 3) * 14}%` }"></div></div></template>
