<script setup>
import { computed, onMounted, ref } from 'vue'
import { useAuthStore } from '../stores/auth'
import { listCategories } from '../services/categories'
import { listMenuItems } from '../services/menuItems'
import BaseBadge from '../components/ui/BaseBadge.vue'
import BaseCard from '../components/ui/BaseCard.vue'
import BaseLoading from '../components/ui/BaseLoading.vue'
import BaseAlert from '../components/ui/BaseAlert.vue'
import { useI18n } from 'vue-i18n'
import PublicMenuPreview from '../components/PublicMenuPreview.vue'

const auth = useAuthStore()
const counts = ref({ categories: 0, items: 0 })
const loading = ref(true)
const error = ref('')
const restaurant = computed(() => auth.user?.restaurant)
const setupItems = computed(() => [restaurant.value?.description, restaurant.value?.logo_url, restaurant.value?.cover_image_url, restaurant.value?.whatsapp, restaurant.value?.opening_hours])
const setupPercent = computed(() => Math.round(setupItems.value.filter(Boolean).length / setupItems.value.length * 100))
const { t, locale } = useI18n()
const restaurantName = computed(() => restaurant.value?.[`name_${locale.value}`] || restaurant.value?.name_ar || restaurant.value?.name_en || restaurant.value?.name)

async function load() {
  loading.value = true; error.value = ''
  try { const [categories, items] = await Promise.all([listCategories(), listMenuItems()]); counts.value = { categories: categories.length, items: items.length } }
  catch { error.value = t('dashboard.loadError') }
  finally { loading.value = false }
}
onMounted(load)
</script>
<template>
  <section class="dashboard-page">
    <header class="dashboard-hero ui-card"><div class="dashboard-identity"><img v-if="restaurant?.logo_url" class="dashboard-logo" :src="restaurant.logo_url" :alt="restaurantName" loading="lazy"><span v-else class="dashboard-logo-fallback" aria-hidden="true">{{ restaurantName?.charAt(0) }}</span><div><span class="eyebrow">{{ $t('dashboard.workspace') }}</span><h1>{{ restaurantName }}</h1><BaseBadge :variant="restaurant?.is_active ? 'success' : 'warning'">{{ restaurant?.is_active ? $t('common.active') : $t('common.inactive') }}</BaseBadge></div></div><div class="header-preview-actions"><PublicMenuPreview :restaurant="restaurant" /><RouterLink class="ui-button ui-button-primary" to="/qr-code">{{ $t('dashboard.qr') }}</RouterLink></div></header>
    <BaseAlert v-if="error" type="warning">{{ error }} <button class="link-button" @click="load">{{ $t('common.retry') }}</button></BaseAlert>
    <BaseLoading v-if="loading" :rows="3" :label="$t('common.loading')" />
    <div v-else class="dashboard-stats"><BaseCard><span>{{ $t('dashboard.categories') }}</span><strong>{{ counts.categories }}</strong><RouterLink to="/categories">{{ $t('dashboard.manageCategories') }}</RouterLink></BaseCard><BaseCard><span>{{ $t('dashboard.items') }}</span><strong>{{ counts.items }}</strong><RouterLink to="/menu-items">{{ $t('dashboard.manageMenu') }}</RouterLink></BaseCard><BaseCard><span>{{ $t('dashboard.setup') }}</span><strong>{{ setupPercent }}%</strong><div class="progress-track"><span :style="{ width: `${setupPercent}%` }"></span></div></BaseCard></div>
    <BaseCard><div class="page-heading"><div><h2>{{ $t('dashboard.quick') }}</h2><p>{{ $t('dashboard.quickHelp') }}</p></div></div><div class="quick-links"><RouterLink to="/restaurant">{{ $t('dashboard.settings') }}</RouterLink><RouterLink to="/categories">{{ $t('dashboard.addCategory') }}</RouterLink><RouterLink to="/menu-items">{{ $t('dashboard.addItem') }}</RouterLink><RouterLink :to="`/menu/${restaurant?.slug}`" target="_blank">{{ $t('dashboard.openMenu') }}</RouterLink></div></BaseCard>
  </section>
</template>
