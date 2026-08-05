<script setup>
import { nextTick, onBeforeUnmount, ref, watch } from 'vue'
const props = defineProps({ open: Boolean, title: { type: String, required: true }, closeable: { type: Boolean, default: true } })
const emit = defineEmits(['close'])
const panel = ref(null)
const titleId = `modal-title-${Math.random().toString(36).slice(2)}`
let previousFocus
function close() { if (props.closeable) emit('close') }
function keydown(event) {
  if (!props.open) return
  if (event.key === 'Escape') close()
  if (event.key !== 'Tab') return
  const focusable = [...panel.value.querySelectorAll('button, input, textarea, select, a[href], [tabindex]:not([tabindex="-1"])')].filter((element) => !element.disabled)
  if (!focusable.length) return
  const first = focusable[0]; const last = focusable.at(-1)
  if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus() }
  else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus() }
}
watch(() => props.open, async (open) => {
  document.body.style.overflow = open ? 'hidden' : ''
  if (open) { previousFocus = document.activeElement; await nextTick(); panel.value?.querySelector('button, input, textarea, select, a[href]')?.focus() }
  else previousFocus?.focus?.()
})
window.addEventListener('keydown', keydown)
onBeforeUnmount(() => { window.removeEventListener('keydown', keydown); document.body.style.overflow = '' })
</script>
<template><Teleport to="body"><div v-if="open" class="ui-modal-backdrop" @mousedown.self="close"><section ref="panel" class="ui-modal" role="dialog" aria-modal="true" :aria-labelledby="titleId"><header><h2 :id="titleId">{{ title }}</h2><button v-if="closeable" type="button" :aria-label="$t('common.closeDialog')" @click="close">×</button></header><div class="ui-modal-body"><slot /></div><footer v-if="$slots.actions"><slot name="actions" /></footer></section></div></Teleport></template>
