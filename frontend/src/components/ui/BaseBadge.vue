<script setup>defineProps({ variant: { type: String, default: 'neutral' } })</script>
<template><span class="ui-badge" :class="`ui-badge-${variant}`"><slot /></span></template>
