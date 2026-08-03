<script setup>
import { reactive, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'

const props = defineProps({
  category: { type: Object, default: null },
  saving: { type: Boolean, default: false },
  serverError: { type: String, default: '' },
})
const emit = defineEmits(['save', 'cancel'])
const form = reactive({ name_ar: '', name_en: '', is_active: true })
const validationError = ref('')
const { t } = useI18n()

watch(
  () => props.category,
  (category) => {
    form.name_ar = category?.name_ar ?? category?.name ?? ''
    form.name_en = category?.name_en ?? ''
    form.is_active = category?.is_active ?? true
    validationError.value = ''
  },
  { immediate: true },
)

function submit() {
  const nameAr = form.name_ar.trim()
  const nameEn = form.name_en.trim()
  if (!nameAr && !nameEn) {
    validationError.value = t('forms.categoryRequired')
    return
  }
  if (nameAr.length > 120 || nameEn.length > 120) {
    validationError.value = t('forms.categoryTooLong')
    return
  }

  validationError.value = ''
  emit('save', { name_ar: nameAr || null, name_en: nameEn || null, is_active: form.is_active })
}
</script>

<template>
  <form class="category-form" @submit.prevent="submit">
    <div>
      <h2>{{ category ? $t('forms.editCategory') : $t('categories.add') }}</h2>
      <p>{{ category ? $t('forms.editCategoryHelp') : $t('forms.addCategoryHelp') }}</p>
    </div>
    <p v-if="validationError || serverError" class="error" role="alert">
      {{ validationError || serverError }}
    </p>
    <label>
      {{ $t('categories.nameAr') }}
      <input v-model="form.name_ar" maxlength="120" autocomplete="off" :disabled="saving">
    </label>
    <label>
      {{ $t('categories.nameEn') }}
      <input v-model="form.name_en" maxlength="120" autocomplete="off" :disabled="saving">
    </label>
    <label class="checkbox-label">
      <input v-model="form.is_active" type="checkbox" :disabled="saving">
      {{ $t('categories.activeVisible') }}
    </label>
    <div class="form-actions">
      <button class="button" type="submit" :disabled="saving">
        {{ saving ? $t('restaurant.saving') : category ? $t('categories.update') : $t('categories.create') }}
      </button>
      <button class="button button-secondary" type="button" :disabled="saving" @click="emit('cancel')">
        {{ $t('common.cancel') }}
      </button>
    </div>
  </form>
</template>
