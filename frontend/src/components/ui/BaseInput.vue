<script setup>
defineOptions({ inheritAttrs: false })
defineProps({ modelValue: { type: [String, Number], default: '' }, label: { type: String, required: true }, error: { type: String, default: '' }, required: Boolean, hint: { type: String, default: '' }, full: Boolean })
defineEmits(['update:modelValue'])
const id = `input-${Math.random().toString(36).slice(2)}`
</script>
<template><label class="ui-field" :class="{ full }" :for="id"><span>{{ label }} <span v-if="required" class="ui-required" aria-hidden="true">*</span></span><input :id="id" v-bind="$attrs" :value="modelValue" :required="required" :aria-invalid="Boolean(error)" :aria-describedby="error || hint ? `${id}-help` : undefined" @input="$emit('update:modelValue', $event.target.value)"><small v-if="error || hint" :id="`${id}-help`" :class="{ 'field-error': error }">{{ error || hint }}</small></label></template>
