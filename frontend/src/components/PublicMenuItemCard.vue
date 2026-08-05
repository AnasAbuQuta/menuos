<script setup>
import { useI18n } from 'vue-i18n'
import AppImage from './AppImage.vue'

defineProps({
  item: { type: Object, required: true },
  formattedPrice: { type: String, required: true },
})
defineEmits(['add', 'view'])
const { t } = useI18n()
</script>

<template>
  <article class="public-menu-card" tabindex="0" @click="$emit('view', item)" @keydown.enter="$emit('view', item)">
    <div class="public-menu-image"><AppImage :src="item.image_url" :alt="item.name" /></div>
    <div class="public-menu-card-body">
      <div class="public-menu-card-title">
        <h3>{{ item.name }}</h3>
        <strong>{{ formattedPrice }}</strong>
      </div>
      <p v-if="item.description">{{ item.description }}</p>
      <span v-if="item.is_featured" class="public-menu-featured">{{ t('public.featured') }}</span>
      <button class="public-menu-add" type="button" :aria-label="t('public.addItem', { name: item.name })" @click.stop="$emit('add', item)">{{ t('public.add') }}</button>
    </div>
  </article>
</template>
