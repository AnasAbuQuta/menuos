<script setup>
import { onBeforeUnmount, ref } from 'vue'

const props = defineProps({
  label: { type: String, required: true },
  guidance: { type: String, required: true },
  currentUrl: { type: String, default: '' },
  busy: { type: Boolean, default: false },
})
const emit = defineEmits(['upload', 'remove'])
const file = ref(null)
const previewUrl = ref('')
const error = ref('')
const allowed = ['image/jpeg', 'image/png', 'image/webp']

function revokePreview() {
  if (previewUrl.value) URL.revokeObjectURL(previewUrl.value)
  previewUrl.value = ''
}

function selectFile(event) {
  revokePreview()
  file.value = null
  error.value = ''
  const selected = event.target.files?.[0]
  if (!selected) return
  if (!allowed.includes(selected.type)) {
    error.value = 'Choose a JPG, PNG, or WebP image.'
    event.target.value = ''
    return
  }
  if (selected.size > 2 * 1024 * 1024) {
    error.value = 'Image size must not exceed 2 MB.'
    event.target.value = ''
    return
  }
  file.value = selected
  previewUrl.value = URL.createObjectURL(selected)
}

function upload() {
  if (file.value && !props.busy) emit('upload', file.value)
}

function remove() {
  if (window.confirm(`Remove the restaurant ${props.label.toLowerCase()}?`)) emit('remove')
}

function reset() {
  file.value = null
  error.value = ''
  revokePreview()
}

defineExpose({ reset })
onBeforeUnmount(revokePreview)
</script>

<template>
  <div class="brand-image-field">
    <div><h3>{{ label }}</h3><p>{{ guidance }}</p></div>
    <div class="brand-image-preview">
      <img v-if="previewUrl || currentUrl" :src="previewUrl || currentUrl" :alt="`Restaurant ${label.toLowerCase()} preview`">
      <span v-else>No {{ label.toLowerCase() }} uploaded</span>
    </div>
    <label>Choose image<input type="file" accept="image/jpeg,image/png,image/webp" :disabled="busy" @change="selectFile"></label>
    <p v-if="error" class="field-error" role="alert">{{ error }}</p>
    <div class="form-actions">
      <button class="button" type="button" :disabled="busy || !file" @click="upload">{{ busy ? 'Uploading…' : previewUrl ? `Upload ${label}` : `Choose ${label}` }}</button>
      <button v-if="currentUrl" class="button button-danger" type="button" :disabled="busy" @click="remove">Remove</button>
    </div>
  </div>
</template>
