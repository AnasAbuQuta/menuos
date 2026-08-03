<script setup>
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const auth = useAuthStore()
const router = useRouter()

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
        <nav v-if="auth.hasRestaurant" aria-label="Main navigation">
          <RouterLink to="/app/dashboard">Dashboard</RouterLink>
          <RouterLink to="/restaurant">Restaurant</RouterLink>
          <RouterLink to="/categories">Categories</RouterLink>
          <RouterLink to="/menu-items">Menu Items</RouterLink>
          <RouterLink to="/qr-code">QR Code</RouterLink>
        </nav>
      </div>
      <div class="header-actions">
        <span>{{ auth.user?.name }}</span>
        <button class="button button-secondary" type="button" @click="logout">Log out</button>
      </div>
    </header>
    <main class="app-content"><RouterView /></main>
  </div>
</template>
