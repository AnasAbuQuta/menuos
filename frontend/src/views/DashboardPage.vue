<script setup>
import { useAuthStore } from '../stores/auth'

const auth = useAuthStore()
</script>

<template>
  <section class="card placeholder-card">
    <img v-if="auth.user?.restaurant?.logo_url" class="dashboard-logo" :src="auth.user.restaurant.logo_url" :alt="`${auth.user.restaurant.name} logo`">
    <p class="eyebrow">Restaurant ready</p>
    <h1>{{ auth.user?.restaurant?.name }}</h1>
    <p>Your MenuOS workspace is ready. Manage your restaurant profile, categories, and menu items from the navigation.</p>
  </section>
</template>
