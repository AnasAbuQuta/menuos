<script setup>
import { computed, onMounted, ref } from 'vue'
import { useAuthStore } from '../stores/auth'
import { listCategories } from '../services/categories'
import { listMenuItems } from '../services/menuItems'
import BaseBadge from '../components/ui/BaseBadge.vue'
import BaseCard from '../components/ui/BaseCard.vue'
import BaseLoading from '../components/ui/BaseLoading.vue'
import BaseAlert from '../components/ui/BaseAlert.vue'

const auth = useAuthStore()
const counts = ref({ categories: 0, items: 0 })
const loading = ref(true)
const error = ref('')
const restaurant = computed(() => auth.user?.restaurant)
const setupItems = computed(() => [restaurant.value?.description, restaurant.value?.logo_url, restaurant.value?.cover_image_url, restaurant.value?.whatsapp, restaurant.value?.opening_hours])
const setupPercent = computed(() => Math.round(setupItems.value.filter(Boolean).length / setupItems.value.length * 100))

async function load() {
  loading.value = true; error.value = ''
  try { const [categories, items] = await Promise.all([listCategories(), listMenuItems()]); counts.value = { categories: categories.length, items: items.length } }
  catch { error.value = 'Some dashboard details could not be loaded.' }
  finally { loading.value = false }
}
onMounted(load)
</script>
<template>
  <section class="dashboard-page">
    <header class="dashboard-hero ui-card"><div class="dashboard-identity"><img v-if="restaurant?.logo_url" class="dashboard-logo" :src="restaurant.logo_url" :alt="`${restaurant.name} logo`" loading="lazy"><span v-else class="dashboard-logo-fallback" aria-hidden="true">{{ restaurant?.name?.charAt(0) }}</span><div><span class="eyebrow">Restaurant workspace</span><h1>{{ restaurant?.name }}</h1><BaseBadge :variant="restaurant?.is_active ? 'success' : 'warning'">{{ restaurant?.is_active ? 'Active' : 'Inactive' }}</BaseBadge></div></div><RouterLink class="ui-button ui-button-primary" to="/qr-code">View QR code</RouterLink></header>
    <BaseAlert v-if="error" type="warning">{{ error }} <button class="link-button" @click="load">Retry</button></BaseAlert>
    <BaseLoading v-if="loading" :rows="3" label="Loading dashboard summary" />
    <div v-else class="dashboard-stats"><BaseCard><span>Categories</span><strong>{{ counts.categories }}</strong><RouterLink to="/categories">Manage categories</RouterLink></BaseCard><BaseCard><span>Menu items</span><strong>{{ counts.items }}</strong><RouterLink to="/menu-items">Manage menu</RouterLink></BaseCard><BaseCard><span>Setup complete</span><strong>{{ setupPercent }}%</strong><div class="progress-track" :aria-label="`${setupPercent}% setup complete`"><span :style="{ width: `${setupPercent}%` }"></span></div></BaseCard></div>
    <BaseCard><div class="page-heading"><div><h2>Quick actions</h2><p>Keep your restaurant profile and menu up to date.</p></div></div><div class="quick-links"><RouterLink to="/restaurant">Restaurant settings</RouterLink><RouterLink to="/categories">Add a category</RouterLink><RouterLink to="/menu-items">Add a menu item</RouterLink><RouterLink :to="`/menu/${restaurant?.slug}`" target="_blank">Open public menu</RouterLink></div></BaseCard>
  </section>
</template>
