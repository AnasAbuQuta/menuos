<script setup>
import { onMounted, ref } from 'vue'
import { getAdminDashboard } from '../../services/admin'
import BaseAlert from '../../components/ui/BaseAlert.vue'
import BaseCard from '../../components/ui/BaseCard.vue'
import BaseEmptyState from '../../components/ui/BaseEmptyState.vue'
import BaseLoading from '../../components/ui/BaseLoading.vue'

const loading = ref(true); const error = ref(''); const data = ref(null)
onMounted(async () => { try { data.value = await getAdminDashboard() } catch { error.value = 'admin.loadError' } finally { loading.value = false } })
const cards = ['total_users', 'total_restaurants', 'active_restaurants', 'suspended_restaurants', 'total_menu_items', 'total_public_menu_views', 'total_whatsapp_clicks', 'total_qr_visits']
</script>

<template><section class="admin-page"><header class="page-heading"><div><p class="eyebrow">MenuOS</p><h1>{{ $t('admin.dashboard') }}</h1><p>{{ $t('admin.dashboardIntro') }}</p></div></header><BaseAlert v-if="error" type="error">{{ $t(error) }}</BaseAlert><BaseLoading v-if="loading" :rows="5" /><template v-else-if="data"><div class="admin-metrics"><BaseCard v-for="key in cards" :key="key"><span>{{ $t(`admin.metrics.${key}`) }}</span><strong>{{ data.metrics[key] ?? 0 }}</strong></BaseCard></div><div class="admin-dashboard-grid"><BaseCard><div class="section-heading"><h2>{{ $t('admin.latestUsers') }}</h2><RouterLink :to="{ name: 'admin-users' }">{{ $t('admin.viewAll') }}</RouterLink></div><BaseEmptyState v-if="!data.latest_users.length" :title="$t('admin.noUsers')" :message="$t('admin.noUsersHelp')" /><ul v-else class="admin-recent-list"><li v-for="user in data.latest_users" :key="user.id"><RouterLink :to="{ name: 'admin-user-detail', params: { id: user.id } }"><strong>{{ user.name }}</strong><span>{{ user.email }}</span></RouterLink></li></ul></BaseCard><BaseCard><div class="section-heading"><h2>{{ $t('admin.latestRestaurants') }}</h2><RouterLink :to="{ name: 'admin-restaurants' }">{{ $t('admin.viewAll') }}</RouterLink></div><BaseEmptyState v-if="!data.latest_restaurants.length" :title="$t('admin.noRestaurants')" :message="$t('admin.noRestaurantsHelp')" /><ul v-else class="admin-recent-list"><li v-for="restaurant in data.latest_restaurants" :key="restaurant.id"><RouterLink :to="{ name: 'admin-restaurant-detail', params: { id: restaurant.id } }"><strong>{{ restaurant.name_ar || restaurant.name_en || restaurant.name }}</strong><span>{{ restaurant.owner?.email }}</span></RouterLink></li></ul></BaseCard></div></template></section></template>
