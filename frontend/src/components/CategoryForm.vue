<script setup>
import { reactive, ref, watch } from 'vue'

const props = defineProps({
  category: { type: Object, default: null },
  saving: { type: Boolean, default: false },
  serverError: { type: String, default: '' },
})
const emit = defineEmits(['save', 'cancel'])
const form = reactive({ name: '', is_active: true })
const validationError = ref('')

watch(
  () => props.category,
  (category) => {
    form.name = category?.name ?? ''
    form.is_active = category?.is_active ?? true
    validationError.value = ''
  },
  { immediate: true },
)

function submit() {
  const name = form.name.trim()
  if (!name) {
    validationError.value = 'Category name is required.'
    return
  }
  if (name.length > 120) {
    validationError.value = 'Category name must not exceed 120 characters.'
    return
  }

  validationError.value = ''
  emit('save', { name, is_active: form.is_active })
}
</script>

<template>
  <form class="category-form" @submit.prevent="submit">
    <div>
      <h2>{{ category ? 'Edit category' : 'Add category' }}</h2>
      <p>{{ category ? 'Update the category details.' : 'Create a section for your future menu items.' }}</p>
    </div>
    <p v-if="validationError || serverError" class="error" role="alert">
      {{ validationError || serverError }}
    </p>
    <label>
      Category name
      <input v-model="form.name" maxlength="120" autocomplete="off" :disabled="saving" required>
    </label>
    <label class="checkbox-label">
      <input v-model="form.is_active" type="checkbox" :disabled="saving">
      Active and visible
    </label>
    <div class="form-actions">
      <button class="button" type="submit" :disabled="saving">
        {{ saving ? 'Saving…' : category ? 'Save changes' : 'Create category' }}
      </button>
      <button class="button button-secondary" type="button" :disabled="saving" @click="emit('cancel')">
        Cancel
      </button>
    </div>
  </form>
</template>
