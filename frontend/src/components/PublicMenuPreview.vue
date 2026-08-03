<script setup>
import AppIcon from './ui/AppIcon.vue'
import { useI18n } from 'vue-i18n'
import { useToastStore } from '../stores/toast'
import { publicMenuUrl } from '../utils/management'

const props = defineProps({ restaurant: { type: Object, default: null }, compact: Boolean })
const { t } = useI18n()
const toast = useToastStore()

function openPreview() {
  if (!props.restaurant?.slug) return
  if (!props.restaurant.is_active) toast.warning(t('management.preview.inactive'))
  window.open(publicMenuUrl(props.restaurant.slug), '_blank', 'noopener,noreferrer')
}
</script>

<template>
  <button class="ui-button ui-button-secondary" type="button" :disabled="!restaurant?.slug" :aria-label="$t('management.preview.open')" :title="$t('management.preview.open')" @click="openPreview">
    <AppIcon v-if="!compact" name="eye" :size="18" /><AppIcon v-else name="external" :size="18" /><span v-if="!compact">{{ $t('management.preview.open') }}</span>
  </button>
</template>
