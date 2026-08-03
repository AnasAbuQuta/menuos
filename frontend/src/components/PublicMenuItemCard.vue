<script setup>
defineProps({
  item: { type: Object, required: true },
  formattedPrice: { type: String, required: true },
})
</script>

<template>
  <article class="public-menu-card">
    <div class="public-menu-image">
      <img v-if="item.image_url" :src="item.image_url" :alt="item.name" loading="lazy" width="640" height="400">
      <span v-else aria-hidden="true">No image</span>
    </div>
    <div class="public-menu-card-body">
      <div class="public-menu-card-title">
        <h3>{{ item.name }}</h3>
        <strong>{{ formattedPrice }}</strong>
      </div>
      <p v-if="item.description">{{ item.description }}</p>
      <span v-if="item.is_featured" class="public-menu-featured">Featured</span>
    </div>
  </article>
</template>
