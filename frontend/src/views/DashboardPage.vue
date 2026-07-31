<script setup>
import { useAuthStore } from '../stores/auth'

const auth = useAuthStore()
</script>

<template>
  <section class="card placeholder-card">
    <p class="eyebrow">Restaurant ready</p>
    <h1>{{ auth.user?.restaurant?.name }}</h1>
    <p>Your MenuOS workspace is ready. Dashboard features will be added in a future sprint.</p>
  </section>
</template>
