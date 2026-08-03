<script setup>defineProps({ title: { type: String, required: true }, message: { type: String, required: true } })</script>
<template><div class="ui-empty"><div class="ui-empty-icon" aria-hidden="true">◇</div><h2>{{ title }}</h2><p>{{ message }}</p><div v-if="$slots.default" class="ui-empty-action"><slot /></div></div></template>
