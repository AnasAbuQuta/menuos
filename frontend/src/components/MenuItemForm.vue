<script setup>
import { computed, nextTick, onBeforeUnmount, reactive, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import ImageEditorModal from './ImageEditorModal.vue'
import { validateImageFile } from '../utils/imageEditor'

const props = defineProps({
  item: { type: Object, default: null },
  categories: { type: Array, required: true },
  saving: { type: Boolean, default: false },
  serverErrors: { type: Object, default: () => ({}) },
})
const emit = defineEmits(['save', 'cancel'])
const form = reactive({ name_ar: '', name_en: '', category_id: '', description_ar: '', description_en: '', price: '', is_available: true, is_featured: false })
const image = ref(null)
const previewUrl = ref('')
const clientErrors = ref({})
const sourceImage = ref(null)
const editorOpen = ref(false)
const currentImage = computed(() => previewUrl.value || props.item?.image_url || '')
const { t, locale } = useI18n()
const categoryName = (category) => category[`name_${locale.value}`] || category.name_ar || category.name_en || category.name

function revokePreview() {
  if (previewUrl.value) URL.revokeObjectURL(previewUrl.value)
  previewUrl.value = ''
}

watch(
  () => props.item,
  (item) => {
    revokePreview()
    form.name_ar = item?.name_ar ?? item?.name ?? ''
    form.name_en = item?.name_en ?? ''
    form.category_id = item?.category?.id ?? props.categories[0]?.id ?? ''
    form.description_ar = item?.description_ar ?? item?.description ?? ''
    form.description_en = item?.description_en ?? ''
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
  const validationError = validateImageFile(file)
  if (validationError) {
    clientErrors.value.image = file.size > 2 * 1024 * 1024 ? t('forms.imageSize') : t('forms.imageType')
    event.target.value = ''
    return
  }
  sourceImage.value = file
  editorOpen.value = true
  event.target.value = ''
}

function acceptEditedImage(file) {
  revokePreview()
  image.value = file
  previewUrl.value = URL.createObjectURL(file)
  editorOpen.value = false
  sourceImage.value = null
}

function validate() {
  const errors = {}
  const nameAr = form.name_ar.trim()
  const nameEn = form.name_en.trim()
  if (!nameAr && !nameEn) errors.name_ar = t('forms.itemRequired')
  else if (nameAr.length > 160 || nameEn.length > 160) errors.name_ar = t('forms.itemTooLong')
  if (!form.category_id) errors.category_id = t('forms.chooseCategory')
  if (!/^\d{1,8}(\.\d{1,2})?$/.test(String(form.price)) || Number(form.price) < 0) {
    errors.price = t('forms.priceInvalid')
  }
  if (form.description_ar.length > 5000 || form.description_en.length > 5000) errors.description_ar = t('forms.descriptionTooLong')
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
  if (form.name_ar.trim()) data.append('name_ar', form.name_ar.trim())
  if (form.name_en.trim()) data.append('name_en', form.name_en.trim())
  data.append('category_id', String(form.category_id))
  data.append('description_ar', form.description_ar)
  data.append('description_en', form.description_en)
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
    <div><h2>{{ item ? $t('forms.editItem') : $t('items.add') }}</h2><p>{{ $t('forms.itemHelp') }}</p></div>
    <div class="item-form-grid">
      <label>{{ $t('items.nameAr') }}<input v-model="form.name_ar" maxlength="160" :disabled="saving"><span v-if="errorFor('name_ar')" class="field-error">{{ errorFor('name_ar') }}</span></label>
      <label>{{ $t('items.nameEn') }}<input v-model="form.name_en" maxlength="160" :disabled="saving"><span v-if="errorFor('name_en')" class="field-error">{{ errorFor('name_en') }}</span></label>
      <label>{{ $t('items.category') }}<select v-model="form.category_id" :disabled="saving" required><option value="" disabled>{{ $t('forms.chooseCategory') }}</option><option v-for="category in categories" :key="category.id" :value="category.id">{{ categoryName(category) }}</option></select><span v-if="errorFor('category_id')" class="field-error">{{ errorFor('category_id') }}</span></label>
      <label>{{ $t('items.price') }}<input v-model="form.price" type="number" min="0" max="99999999.99" step="0.01" inputmode="decimal" :disabled="saving" required><span v-if="errorFor('price')" class="field-error">{{ errorFor('price') }}</span></label>
      <label class="full">{{ $t('items.descriptionAr') }}<textarea v-model="form.description_ar" rows="3" maxlength="5000" :disabled="saving" /><span v-if="errorFor('description_ar')" class="field-error">{{ errorFor('description_ar') }}</span></label>
      <label class="full">{{ $t('items.descriptionEn') }}<textarea v-model="form.description_en" rows="3" maxlength="5000" :disabled="saving" /><span v-if="errorFor('description_en')" class="field-error">{{ errorFor('description_en') }}</span></label>
      <label>{{ $t('items.image') }}<input type="file" accept="image/jpeg,image/png,image/webp" :disabled="saving" @change="selectImage"><span v-if="errorFor('image')" class="field-error">{{ errorFor('image') }}</span></label>
      <div class="image-preview full"><img v-if="currentImage" :src="currentImage" :alt="form.name_ar || form.name_en"><span v-else>{{ $t('common.noImage') }}</span></div>
      <label class="checkbox-label"><input v-model="form.is_available" type="checkbox" :disabled="saving">{{ $t('common.available') }}</label>
      <label class="checkbox-label"><input v-model="form.is_featured" type="checkbox" :disabled="saving">{{ $t('common.featured') }}</label>
    </div>
    <div class="form-actions"><button class="button" type="submit" :disabled="saving">{{ saving ? $t('restaurant.saving') : item ? $t('categories.update') : $t('forms.createItem') }}</button><button class="button button-secondary" type="button" :disabled="saving" @click="emit('cancel')">{{ $t('common.cancel') }}</button></div>
    <ImageEditorModal :open="editorOpen" :file="sourceImage" profile="menuItem" @confirm="acceptEditedImage" @close="editorOpen = false; sourceImage = null" />
  </form>
</template>
