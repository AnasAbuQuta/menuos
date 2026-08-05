<script setup>
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '../stores/auth'
import { listCategories } from '../services/categories'
import { listMenuItems } from '../services/menuItems'
import { getDashboardAnalytics } from '../services/analytics'
import BaseBadge from '../components/ui/BaseBadge.vue'
import BaseCard from '../components/ui/BaseCard.vue'
import BaseLoading from '../components/ui/BaseLoading.vue'
import BaseAlert from '../components/ui/BaseAlert.vue'
import PublicMenuPreview from '../components/PublicMenuPreview.vue'
import AnalyticsChart from '../components/AnalyticsChart.vue'
import AppIcon from '../components/ui/AppIcon.vue'
import { calculateSetupCompletion } from '../utils/setupChecklist'
import { analyticsSourceKey, formatRelativeTime } from '../utils/localization'
import { useLocalizedMeta } from '../composables/useLocalizedMeta'

const emptyAnalytics = { summary: { views: 0, visitors: 0, qr_visits: 0, whatsapp_clicks: 0, phone_clicks: 0, daily_views: 0, weekly_views: 0, monthly_views: 0 }, views_7_days: [], views_30_days: [], top_items: [], top_categories: [], traffic_sources: [], recent_activity: [] }
const auth = useAuthStore()
const { locale, t } = useI18n()
const categories = ref([])
const items = ref([])
const analytics = ref(emptyAnalytics)
const chartRange = ref(7)
const showAllSteps = ref(false)
const loading = ref(true)
const error = ref('')
const restaurant = computed(() => auth.user?.restaurant)
const setup = computed(() => calculateSetupCompletion({ restaurant: restaurant.value, categories: categories.value, items: items.value }))
const visibleSetupSteps = computed(() => showAllSteps.value ? setup.value.steps : setup.value.incomplete.slice(0, 3))
const name = computed(() => locale.value === 'ar' ? (restaurant.value?.name_ar || restaurant.value?.name_en || restaurant.value?.name) : (restaurant.value?.name_en || restaurant.value?.name_ar || restaurant.value?.name))
const chartData = computed(() => chartRange.value === 7 ? analytics.value.views_7_days : analytics.value.views_30_days)
const trafficSources = computed(() => analytics.value.traffic_sources.map((point) => ({ ...point, label: t(`analytics.sources.${analyticsSourceKey(point.label)}`) })))
const statCards = computed(() => [
  { label: t('dashboard.items'), value: items.value.length, to: '/menu-items' },
  { label: t('dashboard.categories'), value: categories.value.length, to: '/categories' },
  { label: t('dashboard.qr'), value: analytics.value.summary.qr_visits, to: '/qr-code' },
  { label: t('dashboard.whatsapp'), value: analytics.value.summary.whatsapp_clicks },
  { label: t('dashboard.views'), value: analytics.value.summary.views },
  { label: t('dashboard.clicks'), value: analytics.value.summary.whatsapp_clicks + analytics.value.summary.phone_clicks },
  { label: t('dashboard.visitors'), value: analytics.value.summary.visitors },
])

function activityLabel(event) { return t(`dashboard.activity.${['menu_view', 'search', 'category_click', 'item_click', 'whatsapp_click', 'phone_click', 'qr_visit'].includes(event.event_type) ? event.event_type : 'other'}`) }
function relativeTime(value) { return formatRelativeTime(value, locale.value) }
function number(value) { return new Intl.NumberFormat(locale.value === 'ar' ? 'ar' : 'en').format(value) }
async function load() {
  loading.value = true
  error.value = ''
  try { [categories.value, items.value, analytics.value] = await Promise.all([listCategories(), listMenuItems(), getDashboardAnalytics()]) }
  catch { error.value = t('dashboard.loadError') }
  finally { loading.value = false }
}

useLocalizedMeta('meta.dashboard')
onMounted(load)
</script>

<template>
  <section class="dashboard-page commercial-dashboard">
    <header class="dashboard-hero ui-card"><div class="dashboard-identity"><img v-if="restaurant?.logo_url" class="dashboard-logo" :src="restaurant.logo_url" :alt="name" width="76" height="76"><span v-else class="dashboard-logo-fallback" aria-hidden="true">{{ name?.charAt(0) }}</span><div><span class="eyebrow">{{ t('dashboard.workspace') }}</span><h1>{{ name }}</h1><div class="dashboard-meta"><BaseBadge :variant="restaurant?.is_active ? 'success' : 'warning'">{{ t(restaurant?.is_active ? 'common.active' : 'common.inactive') }}</BaseBadge><span>{{ t('dashboard.theme', { name: t(`themes.${restaurant?.theme_key || 'modern'}`) }) }}</span><span>{{ t(restaurant?.default_language === 'ar' ? 'dashboard.languageAr' : 'dashboard.languageEn') }}</span></div></div></div><div class="header-preview-actions"><PublicMenuPreview :restaurant="restaurant" /><RouterLink class="ui-button ui-button-primary" to="/menu-items">+ {{ t('dashboard.addItem') }}</RouterLink></div></header>
    <BaseAlert v-if="error" type="warning">{{ error }} <button class="link-button" @click="load">{{ t('dashboard.retry') }}</button></BaseAlert>
    <BaseLoading v-if="loading" :rows="4" :label="t('dashboard.loading')" />
    <template v-else>
      <div class="dashboard-stat-grid"><component :is="card.to ? 'RouterLink' : 'article'" v-for="card in statCards" :key="card.label" :to="card.to" class="dashboard-stat-card ui-card"><span>{{ card.label }}</span><strong>{{ number(card.value) }}</strong><small v-if="card.to">{{ t('dashboard.manage') }} →</small></component></div>
      <div class="dashboard-main-grid">
        <section class="analytics-panel"><div class="section-heading"><div><span class="eyebrow">{{ t('dashboard.analytics') }}</span><h2>{{ t('dashboard.performance') }}</h2></div><div class="range-switch"><button :class="{ active: chartRange === 7 }" @click="chartRange = 7">{{ t('dashboard.days7') }}</button><button :class="{ active: chartRange === 30 }" @click="chartRange = 30">{{ t('dashboard.days30') }}</button></div></div><AnalyticsChart :title="t('dashboard.viewsRange', { count: chartRange })" :data="chartData" /><div class="dashboard-chart-grid"><AnalyticsChart :title="t('dashboard.topCategories')" type="bar" :data="analytics.top_categories" /><AnalyticsChart :title="t('dashboard.topItems')" type="bar" :data="analytics.top_items" /><AnalyticsChart :title="t('dashboard.trafficSources')" type="bar" :data="trafficSources" /></div></section>
        <aside class="dashboard-side"><BaseCard><h2>{{ t('dashboard.quick') }}</h2><div class="dashboard-actions"><RouterLink to="/menu-items">{{ t('dashboard.addItem') }} <kbd>Alt+M</kbd></RouterLink><RouterLink to="/categories">{{ t('dashboard.addCategory') }} <kbd>Alt+C</kbd></RouterLink><RouterLink to="/qr-code">{{ t('qr.download') }}</RouterLink><RouterLink to="/restaurant">{{ t('dashboard.settings') }} <kbd>Alt+S</kbd></RouterLink></div></BaseCard><BaseCard><h2>{{ t('dashboard.recent') }}</h2><ul v-if="analytics.recent_activity.length" class="activity-list"><li v-for="(event, index) in analytics.recent_activity" :key="`${event.occurred_at}-${index}`"><span class="activity-dot" /><div><strong>{{ activityLabel(event) }}</strong><small>{{ relativeTime(event.occurred_at) }}<template v-if="event.source"> · {{ t(`analytics.sources.${analyticsSourceKey(event.source)}`) }}</template></small></div></li></ul><p v-else class="muted">{{ t('dashboard.recentEmpty') }}</p></BaseCard></aside>
      </div>
      <BaseCard class="setup-checklist-card"><div class="setup-summary"><div><span class="eyebrow">{{ t('dashboard.setupProgress') }}</span><h2>{{ number(setup.percentage) }}%</h2><p>{{ t('dashboard.stepsRemaining', { count: setup.incomplete.length }) }}</p></div><div class="progress-track"><span :style="{ width: `${setup.percentage}%` }" /></div></div><ul class="setup-checklist"><li v-for="entry in visibleSetupSteps" :key="entry.key" :class="{ complete: entry.completed }"><span class="setup-check"><AppIcon name="check" :size="17" /></span><div><strong>{{ t(`setup.steps.${entry.key}.label`) }}</strong><p>{{ t(`setup.steps.${entry.key}.help`) }}</p></div><RouterLink v-if="!entry.completed" :to="entry.route">{{ t('dashboard.continue') }}</RouterLink></li></ul><button class="button button-secondary" type="button" @click="showAllSteps = !showAllSteps">{{ t(showAllSteps ? 'dashboard.showLess' : 'dashboard.showAll') }}</button></BaseCard>
      <BaseCard><h2>{{ t('dashboard.quickLinks') }}</h2><div class="quick-links"><RouterLink to="/restaurant">{{ t('dashboard.profile') }}</RouterLink><RouterLink to="/categories">{{ t('dashboard.categories') }}</RouterLink><RouterLink to="/menu-items">{{ t('dashboard.items') }}</RouterLink><RouterLink to="/qr-code">{{ t('qr.title') }}</RouterLink><RouterLink :to="`/menu/${restaurant?.slug}`" target="_blank">{{ t('dashboard.openMenu') }}</RouterLink></div></BaseCard>
    </template>
  </section>
</template>
