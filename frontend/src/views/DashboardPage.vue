<script setup>
import { computed, onMounted, ref } from 'vue'
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
import { themeFor } from '../theme/restaurantThemes'

const emptyAnalytics = { summary: { views: 0, visitors: 0, qr_visits: 0, whatsapp_clicks: 0, phone_clicks: 0, daily_views: 0, weekly_views: 0, monthly_views: 0 }, views_7_days: [], views_30_days: [], top_items: [], top_categories: [], traffic_sources: [], recent_activity: [] }
const auth = useAuthStore()
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
const name = computed(() => restaurant.value?.name_en || restaurant.value?.name_ar || restaurant.value?.name)
const chartData = computed(() => chartRange.value === 7 ? analytics.value.views_7_days : analytics.value.views_30_days)
const statCards = computed(() => [
  { label: 'Menu items', value: items.value.length, to: '/menu-items', icon: 'menu' }, { label: 'Categories', value: categories.value.length, to: '/categories', icon: 'grid' },
  { label: 'QR visits', value: analytics.value.summary.qr_visits, to: '/qr-code', icon: 'qr' }, { label: 'WhatsApp', value: analytics.value.summary.whatsapp_clicks, icon: 'message' },
  { label: 'Views', value: analytics.value.summary.views, icon: 'eye' }, { label: 'Clicks', value: analytics.value.summary.whatsapp_clicks + analytics.value.summary.phone_clicks, icon: 'cursor' }, { label: 'Visitors', value: analytics.value.summary.visitors, icon: 'users' },
])

function activityLabel(event) { return ({ menu_view: 'Menu opened', search: 'Menu searched', category_click: 'Category viewed', item_click: 'Menu item viewed', whatsapp_click: 'WhatsApp clicked', phone_click: 'Phone clicked', qr_visit: 'QR menu opened' })[event.event_type] || 'Menu activity' }
function relativeTime(value) { const minutes = Math.max(0, Math.round((Date.now() - new Date(value).getTime()) / 60000)); return minutes < 1 ? 'Just now' : minutes < 60 ? `${minutes}m ago` : minutes < 1440 ? `${Math.floor(minutes / 60)}h ago` : `${Math.floor(minutes / 1440)}d ago` }
async function load() {
  loading.value = true; error.value = ''
  try { [categories.value, items.value, analytics.value] = await Promise.all([listCategories(), listMenuItems(), getDashboardAnalytics()]) }
  catch { error.value = 'Some dashboard data could not be loaded. Please retry.' }
  finally { loading.value = false }
}
onMounted(load)
</script>

<template>
  <section class="dashboard-page commercial-dashboard">
    <header class="dashboard-hero ui-card"><div class="dashboard-identity"><img v-if="restaurant?.logo_url" class="dashboard-logo" :src="restaurant.logo_url" :alt="name" width="76" height="76"><span v-else class="dashboard-logo-fallback" aria-hidden="true">{{ name?.charAt(0) }}</span><div><span class="eyebrow">Restaurant workspace</span><h1>{{ name }}</h1><div class="dashboard-meta"><BaseBadge :variant="restaurant?.is_active ? 'success' : 'warning'">{{ restaurant?.is_active ? 'Active' : 'Inactive' }}</BaseBadge><span>{{ themeFor(restaurant?.theme_key).name || restaurant?.theme_key }} theme</span><span>{{ restaurant?.default_language === 'ar' ? 'Arabic' : 'English' }}</span></div></div></div><div class="header-preview-actions"><PublicMenuPreview :restaurant="restaurant" /><RouterLink class="ui-button ui-button-primary" to="/menu-items">+ Add menu item</RouterLink></div></header>
    <BaseAlert v-if="error" type="warning">{{ error }} <button class="link-button" @click="load">Retry</button></BaseAlert>
    <BaseLoading v-if="loading" :rows="4" label="Loading dashboard" />
    <template v-else>
      <div class="dashboard-stat-grid"><component :is="card.to ? 'RouterLink' : 'article'" v-for="card in statCards" :key="card.label" :to="card.to" class="dashboard-stat-card ui-card"><span>{{ card.label }}</span><strong>{{ card.value.toLocaleString() }}</strong><small v-if="card.to">Manage →</small></component></div>
      <div class="dashboard-main-grid">
        <section class="analytics-panel"><div class="section-heading"><div><span class="eyebrow">Analytics</span><h2>Restaurant performance</h2></div><div class="range-switch"><button :class="{ active: chartRange === 7 }" @click="chartRange = 7">7 days</button><button :class="{ active: chartRange === 30 }" @click="chartRange = 30">30 days</button></div></div><AnalyticsChart :title="`Views · last ${chartRange} days`" :data="chartData" /><div class="dashboard-chart-grid"><AnalyticsChart title="Top categories" type="bar" :data="analytics.top_categories" /><AnalyticsChart title="Top menu items" type="bar" :data="analytics.top_items" /><AnalyticsChart title="Traffic sources" type="bar" :data="analytics.traffic_sources" /></div></section>
        <aside class="dashboard-side"><BaseCard><h2>Quick actions</h2><div class="dashboard-actions"><RouterLink to="/menu-items">Create menu item <kbd>Alt+M</kbd></RouterLink><RouterLink to="/categories">Create category <kbd>Alt+C</kbd></RouterLink><RouterLink to="/qr-code">Download QR</RouterLink><RouterLink to="/restaurant">Restaurant settings <kbd>Alt+S</kbd></RouterLink></div></BaseCard><BaseCard><h2>Recent activity</h2><ul v-if="analytics.recent_activity.length" class="activity-list"><li v-for="(event, index) in analytics.recent_activity" :key="`${event.occurred_at}-${index}`"><span class="activity-dot" /><div><strong>{{ activityLabel(event) }}</strong><small>{{ relativeTime(event.occurred_at) }}<template v-if="event.source"> · {{ event.source }}</template></small></div></li></ul><p v-else class="muted">Activity will appear as customers use your public menu.</p></BaseCard></aside>
      </div>
      <BaseCard class="setup-checklist-card"><div class="setup-summary"><div><span class="eyebrow">Setup progress</span><h2>{{ setup.percentage }}%</h2><p>{{ setup.incomplete.length }} steps remaining</p></div><div class="progress-track"><span :style="{ width: `${setup.percentage}%` }" /></div></div><ul class="setup-checklist"><li v-for="entry in visibleSetupSteps" :key="entry.key" :class="{ complete: entry.completed }"><span class="setup-check"><AppIcon name="check" :size="17" /></span><div><strong>{{ $t(`setup.steps.${entry.key}.label`) }}</strong><p>{{ $t(`setup.steps.${entry.key}.help`) }}</p></div><RouterLink v-if="!entry.completed" :to="entry.route">Continue</RouterLink></li></ul><button class="button button-secondary" type="button" @click="showAllSteps = !showAllSteps">{{ showAllSteps ? 'Show less' : 'Show all' }}</button></BaseCard>
      <BaseCard><h2>Quick links</h2><div class="quick-links"><RouterLink to="/restaurant">Restaurant profile</RouterLink><RouterLink to="/categories">Categories</RouterLink><RouterLink to="/menu-items">Menu items</RouterLink><RouterLink to="/qr-code">QR code</RouterLink><RouterLink :to="`/menu/${restaurant?.slug}`" target="_blank">Open public menu</RouterLink></div></BaseCard>
    </template>
  </section>
</template>
