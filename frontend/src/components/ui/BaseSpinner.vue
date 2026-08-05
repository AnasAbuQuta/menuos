<script setup>
defineProps({ size: { type: String, default: 'md' }, label: { type: String, default: '' } })
</script>
<template><span class="ui-spinner" :class="`ui-spinner-${size}`" role="status"><span class="sr-only">{{ label || $t('common.loading') }}</span></span></template>
