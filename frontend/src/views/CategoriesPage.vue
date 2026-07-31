<script setup>
import { onMounted, ref } from 'vue'
import CategoryForm from '../components/CategoryForm.vue'
import { apiError } from '../services/api'
import {
  createCategory,
  deleteCategory,
  listCategories,
  reorderCategories,
  updateCategory,
} from '../services/categories'

const categories = ref([])
const loading = ref(true)
const saving = ref(false)
const reordering = ref(false)
const pageError = ref('')
const formError = ref('')
const success = ref('')
const showForm = ref(false)
const editingCategory = ref(null)

async function load() {
  loading.value = true
  pageError.value = ''
  try {
    categories.value = await listCategories()
  } catch (error) {
    pageError.value = apiError(error, 'Unable to load categories.')
  } finally {
    loading.value = false
  }
}

function openCreate() {
  editingCategory.value = null
  formError.value = ''
  showForm.value = true
}

function openEdit(category) {
  editingCategory.value = category
  formError.value = ''
  showForm.value = true
}

function closeForm() {
  if (saving.value) return
  showForm.value = false
  editingCategory.value = null
  formError.value = ''
}

async function save(payload) {
  if (saving.value) return
  saving.value = true
  formError.value = ''
  success.value = ''
  try {
    if (editingCategory.value) {
      const updated = await updateCategory(editingCategory.value.id, payload)
      categories.value = categories.value.map((category) => category.id === updated.id ? updated : category)
      success.value = 'Category updated successfully.'
    } else {
      categories.value.push(await createCategory(payload))
      success.value = 'Category created successfully.'
    }
    closeForm()
  } catch (error) {
    formError.value = apiError(error, 'Unable to save the category.')
  } finally {
    saving.value = false
    if (!formError.value) closeForm()
  }
}

async function remove(category) {
  const confirmed = window.confirm(`Remove “${category.name}”? This category will be permanently deleted.`)
  if (!confirmed) return

  pageError.value = ''
  success.value = ''
  try {
    await deleteCategory(category.id)
    categories.value = categories.value.filter((item) => item.id !== category.id)
    categories.value.forEach((item, index) => { item.sort_order = index })
    success.value = 'Category deleted successfully.'
  } catch (error) {
    pageError.value = apiError(error, 'Unable to delete the category.')
  }
}

async function move(index, offset) {
  const target = index + offset
  if (target < 0 || target >= categories.value.length || reordering.value) return

  const previous = [...categories.value]
  const reordered = [...categories.value]
  ;[reordered[index], reordered[target]] = [reordered[target], reordered[index]]
  categories.value = reordered
  reordering.value = true
  pageError.value = ''
  success.value = ''

  try {
    categories.value = await reorderCategories(reordered.map(({ id }) => id))
    success.value = 'Category order saved.'
  } catch (error) {
    categories.value = previous
    pageError.value = apiError(error, 'Unable to save category order.')
  } finally {
    reordering.value = false
  }
}

onMounted(load)
</script>

<template>
  <section class="categories-page">
    <div class="page-heading">
      <div>
        <p class="eyebrow">Menu structure</p>
        <h1>Categories</h1>
        <p>Organize the sections of your restaurant menu.</p>
      </div>
      <button class="button" type="button" @click="openCreate">Add category</button>
    </div>

    <p v-if="success" class="success" role="status">{{ success }}</p>
    <div v-if="pageError" class="error-state" role="alert">
      <p>{{ pageError }}</p>
      <button class="button button-secondary" type="button" @click="load">Try again</button>
    </div>

    <div v-if="showForm" class="card category-form-card">
      <CategoryForm
        :category="editingCategory"
        :saving="saving"
        :server-error="formError"
        @save="save"
        @cancel="closeForm"
      />
    </div>

    <div v-if="loading" class="card state-card" aria-live="polite">Loading categories…</div>
    <div v-else-if="!pageError && categories.length === 0" class="card state-card">
      <h2>No categories yet</h2>
      <p>Add your first category to start organizing your menu.</p>
      <button class="button" type="button" @click="openCreate">Add first category</button>
    </div>

    <ul v-else-if="!pageError" class="category-list" aria-label="Restaurant categories">
      <li v-for="(category, index) in categories" :key="category.id" class="card category-row">
        <div class="category-info">
          <strong>{{ category.name }}</strong>
          <span :class="['status-badge', { inactive: !category.is_active }]">
            {{ category.is_active ? 'Active' : 'Inactive' }}
          </span>
        </div>
        <div class="category-actions">
          <button class="icon-button" type="button" :disabled="index === 0 || reordering" :aria-label="`Move ${category.name} up`" @click="move(index, -1)">↑</button>
          <button class="icon-button" type="button" :disabled="index === categories.length - 1 || reordering" :aria-label="`Move ${category.name} down`" @click="move(index, 1)">↓</button>
          <button class="button button-secondary" type="button" @click="openEdit(category)">Edit</button>
          <button class="button button-danger" type="button" @click="remove(category)">Delete</button>
        </div>
      </li>
    </ul>
  </section>
</template>
