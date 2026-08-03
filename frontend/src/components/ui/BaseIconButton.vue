<script setup>
defineProps({ label: { type: String, required: true }, danger: Boolean, active: Boolean, disabled: Boolean })
</script>

<template>
  <button class="icon-action" :class="{ danger, active }" type="button" :disabled="disabled" :aria-label="label" :title="label">
    <slot />
  </button>
</template>
