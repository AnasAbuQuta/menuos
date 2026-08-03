<script setup>
defineProps({ modelValue: Boolean, label: { type: String, required: true }, disabled: Boolean })
defineEmits(['update:modelValue'])
</script>

<template>
  <button class="ui-toggle" :class="{ active: modelValue }" type="button" role="switch" :aria-checked="modelValue" :aria-label="label" :title="label" :disabled="disabled" @click="$emit('update:modelValue', !modelValue)">
    <span aria-hidden="true" />
  </button>
</template>
