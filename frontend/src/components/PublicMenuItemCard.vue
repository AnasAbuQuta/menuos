<script setup>
defineProps({
  item: { type: Object, required: true },
  formattedPrice: { type: String, required: true },
})
defineEmits(['add'])
import AppImage from './AppImage.vue'
</script>

<template>
  <article class="public-menu-card">
    <div class="public-menu-image"><AppImage :src="item.image_url" :alt="item.name" /></div>
    <div class="public-menu-card-body">
      <div class="public-menu-card-title">
        <h3>{{ item.name }}</h3>
        <strong>{{ formattedPrice }}</strong>
      </div>
      <p v-if="item.description">{{ item.description }}</p>
      <span v-if="item.is_featured" class="public-menu-featured">Featured</span>
      <button class="public-menu-add" type="button" :aria-label="`Add ${item.name} to cart`" @click="$emit('add', item)">Add to cart</button>
    </div>
  </article>
</template>
