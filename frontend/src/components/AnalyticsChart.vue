<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
const props = defineProps({ title: { type: String, required: true }, data: { type: Array, default: () => [] }, type: { type: String, default: 'line' } })
const { locale, t } = useI18n()
const max = computed(() => Math.max(1, ...props.data.map((point) => Number(point.value))))
const points = computed(() => props.data.map((point, index) => `${props.data.length === 1 ? 50 : index * 100 / (props.data.length - 1)},${92 - Number(point.value) / max.value * 78}`).join(' '))
function number(value) { return new Intl.NumberFormat(locale.value === 'ar' ? 'ar' : 'en').format(value) }
</script>
<template>
  <section class="analytics-chart ui-card"><header><h3>{{ title }}</h3><span>{{ number(data.reduce((sum, point) => sum + Number(point.value), 0)) }} {{ t('analytics.total') }}</span></header><div v-if="data.length" class="chart-canvas" role="img" :aria-label="`${title}: ${data.map((point) => `${point.label} ${number(point.value)}`).join(', ')}`"><svg v-if="type === 'line'" viewBox="0 0 100 100" preserveAspectRatio="none" aria-hidden="true"><defs><linearGradient :id="`fill-${title.replace(/\W/g, '')}`" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="var(--color-brand)" stop-opacity=".3" /><stop offset="1" stop-color="var(--color-brand)" stop-opacity="0" /></linearGradient></defs><polygon :points="`0,100 ${points} 100,100`" :fill="`url(#fill-${title.replace(/\W/g, '')})`" /><polyline :points="points" fill="none" stroke="var(--color-brand)" stroke-width="2" vector-effect="non-scaling-stroke" /></svg><div v-else class="bar-chart"><div v-for="point in data" :key="point.label" class="bar-row"><span>{{ point.label }}</span><i><b :style="{ width: `${Number(point.value) / max * 100}%` }" /></i><strong>{{ number(point.value) }}</strong></div></div></div><p v-else class="chart-empty">{{ t('analytics.empty') }}</p></section>
</template>
