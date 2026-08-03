<script setup>
import { computed, nextTick, onBeforeUnmount, reactive, ref, watch } from 'vue'

const props = defineProps({
  item: { type: Object, default: null },
  categories: { type: Array, required: true },
  saving: { type: Boolean, default: false },
  serverErrors: { type: Object, default: () => ({}) },
})
const emit = defineEmits(['save', 'cancel'])
const form = reactive({ name: '', category_id: '', description: '', price: '', is_available: true, is_featured: false })
const image = ref(null)
const previewUrl = ref('')
const clientErrors = ref({})
const allowedTypes = ['image/jpeg', 'image/png', 'image/webp']
const currentImage = computed(() => previewUrl.value || props.item?.image_url || '')

function revokePreview() {
  if (previewUrl.value) URL.revokeObjectURL(previewUrl.value)
  previewUrl.value = ''
}

watch(
  () => props.item,
  (item) => {
    revokePreview()
    form.name = item?.name ?? ''
    form.category_id = item?.category?.id ?? props.categories[0]?.id ?? ''
    form.description = item?.description ?? ''
    form.price = item?.price ?? ''
    form.is_available = item?.is_available ?? true
    form.is_featured = item?.is_featured ?? false
    image.value = null
    clientErrors.value = {}
  },
  { immediate: true },
)

function selectImage(event) {
  revokePreview()
  const file = event.target.files?.[0] ?? null
  image.value = null
  clientErrors.value.image = ''
  if (!file) return
  if (!allowedTypes.includes(file.type)) {
    clientErrors.value.image = 'Choose a JPG, PNG, or WebP image.'
    event.target.value = ''
    return
  }
  if (file.size > 2 * 1024 * 1024) {
    clientErrors.value.image = 'Image size must not exceed 2 MB.'
    event.target.value = ''
    return
  }
  image.value = file
  previewUrl.value = URL.createObjectURL(file)
}

function validate() {
  const errors = {}
  const name = form.name.trim()
  if (!name) errors.name = 'Item name is required.'
  else if (name.length > 160) errors.name = 'Item name must not exceed 160 characters.'
  if (!form.category_id) errors.category_id = 'Choose a category.'
  if (!/^\d{1,8}(\.\d{1,2})?$/.test(String(form.price)) || Number(form.price) < 0) {
    errors.price = 'Enter a non-negative price with no more than two decimal places.'
  }
  if (form.description.length > 5000) errors.description = 'Description must not exceed 5,000 characters.'
  if (clientErrors.value.image) errors.image = clientErrors.value.image
  clientErrors.value = errors
  return Object.keys(errors).length === 0
}

async function submit() {
  if (!validate() || props.saving) {
    await nextTick()
    document.querySelector('.menu-item-form .field-error')?.closest('label')?.querySelector('input, textarea, select')?.focus()
    return
  }
  const data = new FormData()
  data.append('name', form.name.trim())
  data.append('category_id', String(form.category_id))
  data.append('description', form.description)
  data.append('price', String(form.price))
  data.append('is_available', form.is_available ? '1' : '0')
  data.append('is_featured', form.is_featured ? '1' : '0')
  if (image.value) data.append('image', image.value)
  emit('save', data)
}

function errorFor(field) {
  return clientErrors.value[field] || props.serverErrors[field]?.[0] || ''
}

onBeforeUnmount(revokePreview)
</script>

<template>
  <form class="menu-item-form" @submit.prevent="submit">
    <div><h2>{{ item ? 'Edit menu item' : 'Add menu item' }}</h2><p>Set the core item details and availability.</p></div>
    <div class="item-form-grid">
      <label>Name<input v-model="form.name" maxlength="160" :disabled="saving" required><span v-if="errorFor('name')" class="field-error">{{ errorFor('name') }}</span></label>
      <label>Category<select v-model="form.category_id" :disabled="saving" required><option value="" disabled>Select category</option><option v-for="category in categories" :key="category.id" :value="category.id">{{ category.name }}</option></select><span v-if="errorFor('category_id')" class="field-error">{{ errorFor('category_id') }}</span></label>
      <label class="full">Description<textarea v-model="form.description" rows="3" maxlength="5000" :disabled="saving" /><span v-if="errorFor('description')" class="field-error">{{ errorFor('description') }}</span></label>
      <label>Price<input v-model="form.price" type="number" min="0" max="99999999.99" step="0.01" inputmode="decimal" :disabled="saving" required><span v-if="errorFor('price')" class="field-error">{{ errorFor('price') }}</span></label>
      <label>Image<input type="file" accept="image/jpeg,image/png,image/webp" :disabled="saving" @change="selectImage"><span v-if="errorFor('image')" class="field-error">{{ errorFor('image') }}</span></label>
      <div class="image-preview full"><img v-if="currentImage" :src="currentImage" :alt="`Preview for ${form.name || 'menu item'}`"><span v-else>No image selected</span></div>
      <label class="checkbox-label"><input v-model="form.is_available" type="checkbox" :disabled="saving">Available</label>
      <label class="checkbox-label"><input v-model="form.is_featured" type="checkbox" :disabled="saving">Featured</label>
    </div>
    <div class="form-actions"><button class="button" type="submit" :disabled="saving">{{ saving ? 'Saving…' : item ? 'Save changes' : 'Create item' }}</button><button class="button button-secondary" type="button" :disabled="saving" @click="emit('cancel')">Cancel</button></div>
  </form>
</template>
