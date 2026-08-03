<script setup>
import { onBeforeUnmount, ref } from 'vue'
import BaseConfirmDialog from './ui/BaseConfirmDialog.vue'
import ImageEditorModal from './ImageEditorModal.vue'
import { formatFileSize, validateImageFile } from '../utils/imageEditor'

const props = defineProps({
  label: { type: String, required: true },
  guidance: { type: String, required: true },
  currentUrl: { type: String, default: '' },
  busy: { type: Boolean, default: false },
  progress: { type: Number, default: 0 },
  profile: { type: String, default: 'logo' },
})
const emit = defineEmits(['upload', 'remove'])
const file = ref(null)
const previewUrl = ref('')
const error = ref('')
const sourceFile = ref(null)
const editorOpen = ref(false)
const confirmingRemove = ref(false)

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
  const validationError = validateImageFile(selected)
  if (validationError) {
    error.value = validationError
    event.target.value = ''
    return
  }
  sourceFile.value = selected
  editorOpen.value = true
  event.target.value = ''
}

function acceptEditedImage(selected) {
  revokePreview()
  file.value = selected
  previewUrl.value = URL.createObjectURL(selected)
  editorOpen.value = false
  sourceFile.value = null
}

function upload() {
  if (file.value && !props.busy) emit('upload', file.value)
}

function remove() {
  confirmingRemove.value = true
}

function reset() {
  file.value = null
  sourceFile.value = null
  editorOpen.value = false
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
      <img v-if="previewUrl || currentUrl" :src="previewUrl || currentUrl" :alt="`Restaurant ${label.toLowerCase()} preview`" loading="lazy">
      <span v-else>No {{ label.toLowerCase() }} uploaded</span>
    </div>
    <label>Choose image<input type="file" accept="image/jpeg,image/png,image/webp" :disabled="busy" @change="selectFile"></label>
    <p v-if="error" class="field-error" role="alert">{{ error }}</p>
    <p v-if="file" class="image-ready">Ready to upload · {{ formatFileSize(file.size) }}</p>
    <div v-if="busy" class="upload-progress" :class="{ indeterminate: progress === 0 }" role="progressbar" aria-label="Image upload progress" :aria-valuenow="progress" aria-valuemin="0" aria-valuemax="100"><span :style="{ width: `${progress}%` }"></span></div>
    <div class="form-actions">
      <button class="button" type="button" :disabled="busy || !file" @click="upload">{{ busy ? 'Uploading…' : previewUrl ? `Upload ${label}` : `Choose ${label}` }}</button>
      <button v-if="currentUrl" class="button button-danger" type="button" :disabled="busy" @click="remove">Remove</button>
    </div>
    <BaseConfirmDialog :open="confirmingRemove" :title="`Remove ${label}?`" :message="`Remove the restaurant ${label.toLowerCase()}?`" confirm-label="Remove image" danger :loading="busy" @confirm="confirmingRemove = false; emit('remove')" @cancel="confirmingRemove = false" />
    <ImageEditorModal :open="editorOpen" :file="sourceFile" :profile="profile" @confirm="acceptEditedImage" @close="editorOpen = false; sourceFile = null" />
  </div>
</template>
