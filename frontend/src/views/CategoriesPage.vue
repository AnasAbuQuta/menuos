<script setup>
import { onMounted, ref } from 'vue'
import CategoryForm from '../components/CategoryForm.vue'
import { apiError } from '../services/api'
import BaseConfirmDialog from '../components/ui/BaseConfirmDialog.vue'
import BaseLoading from '../components/ui/BaseLoading.vue'
import BaseEmptyState from '../components/ui/BaseEmptyState.vue'
import { useToastStore } from '../stores/toast'
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
const toast = useToastStore()
const pendingDelete = ref(null)
const deleting = ref(false)
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
  try {
    if (editingCategory.value) {
      const updated = await updateCategory(editingCategory.value.id, payload)
      categories.value = categories.value.map((category) => category.id === updated.id ? updated : category)
      toast.success('Category updated successfully.')
    } else {
      categories.value.push(await createCategory(payload))
      toast.success('Category created successfully.')
    }
    closeForm()
  } catch (error) {
    formError.value = apiError(error, 'Unable to save the category.')
  } finally {
    saving.value = false
    if (!formError.value) closeForm()
  }
}

function remove(category) { pendingDelete.value = category }
async function confirmRemove() {
  const category = pendingDelete.value
  if (!category || deleting.value) return
  deleting.value = true
  pageError.value = ''
  try {
    await deleteCategory(category.id)
    categories.value = categories.value.filter((item) => item.id !== category.id)
    categories.value.forEach((item, index) => { item.sort_order = index })
    toast.success('Category deleted successfully.')
    pendingDelete.value = null
  } catch (error) {
    pageError.value = apiError(error, 'Unable to delete the category.')
  } finally { deleting.value = false }
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

  try {
    categories.value = await reorderCategories(reordered.map(({ id }) => id))
    toast.success('Category order saved.')
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

    <BaseLoading v-if="loading" :rows="4" label="Loading categories" />
    <BaseEmptyState v-else-if="!pageError && categories.length === 0" title="No categories yet" message="Add your first category to start organizing your menu."><button class="button" type="button" @click="openCreate">Add first category</button></BaseEmptyState>

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
    <BaseConfirmDialog :open="Boolean(pendingDelete)" title="Delete category?" :message="`Remove “${pendingDelete?.name}”? This cannot be undone.`" confirm-label="Delete category" danger :loading="deleting" @confirm="confirmRemove" @cancel="pendingDelete = null" />
  </section>
</template>
