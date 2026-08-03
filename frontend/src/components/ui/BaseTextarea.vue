<script setup>
defineOptions({ inheritAttrs: false })
defineProps({ modelValue: { type: String, default: '' }, label: { type: String, required: true }, error: { type: String, default: '' }, required: Boolean, maxlength: { type: Number, default: undefined }, full: Boolean })
defineEmits(['update:modelValue'])
const id = `textarea-${Math.random().toString(36).slice(2)}`
</script>
<template><label class="ui-field" :class="{ full }" :for="id"><span>{{ label }} <span v-if="required" class="ui-required" aria-hidden="true">*</span></span><textarea :id="id" v-bind="$attrs" :value="modelValue" :required="required" :maxlength="maxlength" :aria-invalid="Boolean(error)" :aria-describedby="`${id}-help`" @input="$emit('update:modelValue', $event.target.value)" /><small :id="`${id}-help`" :class="{ 'field-error': error }">{{ error || (maxlength ? `${modelValue.length}/${maxlength}` : '') }}</small></label></template>
