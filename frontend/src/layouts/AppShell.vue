<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import BaseButton from '../components/ui/BaseButton.vue'

const auth = useAuthStore()
const router = useRouter()
const menuOpen = ref(false)

async function logout() {
  await auth.logout()
  await router.push({ name: 'login' })
}
</script>

<template>
  <div class="app-shell">
    <header class="app-header">
      <div class="app-navigation">
        <RouterLink class="brand" to="/app/dashboard">MenuOS</RouterLink>
        <button v-if="auth.hasRestaurant" class="mobile-nav-toggle" type="button" :aria-expanded="menuOpen" aria-controls="main-navigation" aria-label="Toggle navigation" @click="menuOpen = !menuOpen">☰</button>
        <nav v-if="auth.hasRestaurant" id="main-navigation" :class="{ open: menuOpen }" aria-label="Main navigation" @click="menuOpen = false">
          <RouterLink to="/app/dashboard">Dashboard</RouterLink>
          <RouterLink to="/restaurant">Restaurant</RouterLink>
          <RouterLink to="/categories">Categories</RouterLink>
          <RouterLink to="/menu-items">Menu Items</RouterLink>
          <RouterLink to="/qr-code">QR Code</RouterLink>
        </nav>
      </div>
      <div class="header-actions">
        <span>{{ auth.user?.name }}</span>
        <BaseButton variant="ghost" @click="logout">Log out</BaseButton>
      </div>
    </header>
    <main class="app-content"><RouterView /></main>
  </div>
</template>
