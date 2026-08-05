<script setup>
import BaseButton from './BaseButton.vue'
import BaseModal from './BaseModal.vue'
defineProps({ open: Boolean, title: { type: String, default: '' }, message: { type: String, required: true }, loading: Boolean, confirmLabel: { type: String, default: '' }, danger: { type: Boolean, default: false } })
defineEmits(['confirm', 'cancel'])
</script>
<template><BaseModal :open="open" :title="title || $t('common.confirm')" :closeable="!loading" @close="$emit('cancel')"><p>{{ message }}</p><template #actions><BaseButton variant="ghost" :disabled="loading" @click="$emit('cancel')">{{ $t('common.cancel') }}</BaseButton><BaseButton :variant="danger ? 'danger' : 'primary'" :loading="loading" @click="$emit('confirm')">{{ confirmLabel || $t('common.confirm') }}</BaseButton></template></BaseModal></template>
