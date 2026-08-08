<script setup>
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import BaseButton from '../components/ui/BaseButton.vue'
import LanguageSwitcher from '../components/LanguageSwitcher.vue'

const auth = useAuthStore()
const router = useRouter()
async function logout() { await auth.logout(); await router.push({ name: 'login' }) }
</script>

<template>
  <div class="admin-shell">
    <header class="admin-header">
      <RouterLink class="admin-brand" :to="{ name: 'admin-dashboard' }"><strong>MenuOS</strong><span>{{ $t('admin.platformAdmin') }}</span></RouterLink>
      <nav :aria-label="$t('admin.navigation')">
        <RouterLink :to="{ name: 'admin-dashboard' }">{{ $t('admin.dashboard') }}</RouterLink>
        <RouterLink :to="{ name: 'admin-users' }">{{ $t('admin.users') }}</RouterLink>
        <RouterLink :to="{ name: 'admin-restaurants' }">{{ $t('admin.restaurants') }}</RouterLink>
      </nav>
      <div class="admin-header-actions"><LanguageSwitcher /><RouterLink v-if="auth.hasRestaurant" class="ui-button ui-button-ghost" :to="{ name: 'dashboard' }">{{ $t('admin.ownerArea') }}</RouterLink><BaseButton variant="ghost" @click="logout">{{ $t('nav.logout') }}</BaseButton></div>
    </header>
    <main class="admin-content"><RouterView /></main>
  </div>
</template>
