<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import BaseButton from '../components/ui/BaseButton.vue'
import LanguageSwitcher from '../components/LanguageSwitcher.vue'
import PwaInstallPrompt from '../components/PwaInstallPrompt.vue'
import { prefetchRouteComponent } from '../utils/routePrefetch'

const auth = useAuthStore()
const router = useRouter()
const menuOpen = ref(false)

async function logout() {
  await auth.logout()
  await router.push({ name: 'login' })
}
function keyboardShortcuts(event) { if (!event.altKey || event.ctrlKey || event.metaKey) return; const route = ({ m: 'menu-items', c: 'categories', s: 'restaurant-settings', d: 'dashboard' })[event.key.toLowerCase()]; if (route) { event.preventDefault(); router.push({ name: route }) } }
function prefetch(routeName) { void prefetchRouteComponent(router, routeName) }
onMounted(() => window.addEventListener('keydown', keyboardShortcuts))
onBeforeUnmount(() => window.removeEventListener('keydown', keyboardShortcuts))
</script>

<template>
  <div class="app-shell">
    <header class="app-header">
      <div class="app-navigation">
        <RouterLink class="brand" to="/app/dashboard">MenuOS</RouterLink>
        <button v-if="auth.hasRestaurant" class="mobile-nav-toggle" type="button" :aria-expanded="menuOpen" aria-controls="main-navigation" :aria-label="$t('nav.toggle')" @click="menuOpen = !menuOpen">☰</button>
        <nav v-if="auth.hasRestaurant" id="main-navigation" :class="{ open: menuOpen }" :aria-label="$t('nav.toggle')" @click="menuOpen = false">
          <RouterLink to="/app/dashboard" @pointerenter="prefetch('dashboard')">{{ $t('nav.dashboard') }}</RouterLink>
          <RouterLink to="/restaurant" @pointerenter="prefetch('restaurant-settings')">{{ $t('nav.restaurant') }}</RouterLink>
          <RouterLink to="/categories" @pointerenter="prefetch('categories')">{{ $t('nav.categories') }}</RouterLink>
          <RouterLink to="/menu-items" @pointerenter="prefetch('menu-items')">{{ $t('nav.menuItems') }}</RouterLink>
          <RouterLink to="/qr-code" @pointerenter="prefetch('qr-code')">{{ $t('nav.qr') }}</RouterLink>
        </nav>
      </div>
      <div class="header-actions">
        <LanguageSwitcher /><span>{{ auth.user?.name }}</span>
        <BaseButton variant="ghost" @click="logout">{{ $t('nav.logout') }}</BaseButton>
      </div>
    </header>
    <main class="app-content"><RouterView /></main><PwaInstallPrompt />
  </div>
</template>
