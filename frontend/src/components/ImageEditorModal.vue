<script setup>
import { nextTick, onBeforeUnmount, ref, watch } from 'vue'
import BaseModal from './ui/BaseModal.vue'
import { IMAGE_PROFILES, processCroppedCanvas } from '../utils/imageEditor'

const props = defineProps({ open: Boolean, file: { type: File, default: null }, profile: { type: String, default: 'menuItem' } })
const emit = defineEmits(['close', 'confirm'])
const host = ref(null)
const source = ref('')
const busy = ref(false)
const error = ref('')
const activeRatio = ref(1)
let cropper

function cleanup() {
  cropper?.destroy()
  cropper = null
  if (source.value) URL.revokeObjectURL(source.value)
  source.value = ''
}

async function initialize() {
  cleanup(); error.value = ''
  if (!props.open || !props.file) return
  const profile = IMAGE_PROFILES[props.profile]
  activeRatio.value = profile.aspectRatio
  source.value = URL.createObjectURL(props.file)
  await nextTick()
  try {
    const { default: Cropper } = await import('cropperjs')
    cropper = new Cropper(host.value.querySelector('img'), { container: host.value, template: `<cropper-canvas background><cropper-image rotatable scalable skewable translatable></cropper-image><cropper-shade hidden></cropper-shade><cropper-handle action="select" plain></cropper-handle><cropper-selection initial-coverage="0.85" aspect-ratio="${activeRatio.value}" movable resizable><cropper-grid role="grid" covered></cropper-grid><cropper-crosshair centered></cropper-crosshair><cropper-handle action="move" theme-color="rgba(255,255,255,.35)"></cropper-handle><cropper-handle action="n-resize"></cropper-handle><cropper-handle action="e-resize"></cropper-handle><cropper-handle action="s-resize"></cropper-handle><cropper-handle action="w-resize"></cropper-handle><cropper-handle action="ne-resize"></cropper-handle><cropper-handle action="nw-resize"></cropper-handle><cropper-handle action="se-resize"></cropper-handle><cropper-handle action="sw-resize"></cropper-handle></cropper-selection></cropper-canvas>` })
  } catch { error.value = 'The image editor could not be loaded. Please try again.' }
}

function image() { return cropper?.getCropperImage() }
function selection() { return cropper?.getCropperSelection() }
function zoom(amount) { image()?.$zoom(amount) }
function rotate(degrees) { image()?.$rotate(`${degrees}deg`) }
function reset() { image()?.$resetTransform(); selection()?.$reset(); setRatio(IMAGE_PROFILES[props.profile].aspectRatio) }
function setRatio(value) { activeRatio.value = value; selection()?.$change(0, 0, undefined, undefined, value); selection()?.$center() }

async function confirm() {
  if (!selection() || busy.value) return
  busy.value = true; error.value = ''
  try {
    const canvas = await selection().$toCanvas()
    emit('confirm', await processCroppedCanvas(canvas, props.file, IMAGE_PROFILES[props.profile]))
  } catch (exception) { error.value = exception.message || 'Could not process this image.' }
  finally { busy.value = false }
}

watch(() => [props.open, props.file], initialize)
onBeforeUnmount(cleanup)
</script>

<template>
  <BaseModal :open="open" :title="`Edit ${IMAGE_PROFILES[profile].label}`" :closeable="!busy" @close="emit('close')">
    <div class="image-editor">
      <div ref="host" class="image-editor-stage"><img v-if="source" :src="source" alt="Image being edited"></div>
      <p v-if="error" class="field-error" role="alert">{{ error }}</p>
      <div class="image-editor-ratios" aria-label="Crop shape"><button v-for="ratio in IMAGE_PROFILES[profile].ratios" :key="ratio.label" type="button" :class="{ active: activeRatio === ratio.value }" @click="setRatio(ratio.value)">{{ ratio.label }}</button></div>
      <div class="image-editor-tools"><button type="button" @click="zoom(-0.1)">− Zoom</button><button type="button" @click="zoom(0.1)">+ Zoom</button><button type="button" @click="rotate(-90)">↶ Rotate</button><button type="button" @click="rotate(90)">↷ Rotate</button><button type="button" @click="reset">Reset</button></div>
      <p class="image-editor-hint">Drag the image and resize the crop area. The result is optimized before upload.</p>
    </div>
    <template #actions><button class="button button-secondary" type="button" :disabled="busy" @click="emit('close')">Cancel</button><button class="button" type="button" :disabled="busy || !source" @click="confirm">{{ busy ? 'Processing…' : 'Use image' }}</button></template>
  </BaseModal>
</template>
