<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import MenuItemForm from '../components/MenuItemForm.vue'
import { apiError } from '../services/api'
import { listCategories } from '../services/categories'
import { createMenuItem, deleteMenuItem, listMenuItems, reorderMenuItems, updateMenuItem } from '../services/menuItems'

const items = ref([])
const categories = ref([])
const filters = reactive({ search: '', category_id: '', is_available: '', is_featured: '' })
const loading = ref(true)
const saving = ref(false)
const reordering = ref(false)
const showForm = ref(false)
const editingItem = ref(null)
const pageError = ref('')
const formErrors = ref({})
const success = ref('')
const reorderEnabled = computed(() => !filters.search && !filters.category_id && filters.is_available === '' && filters.is_featured === '')

async function load() {
  loading.value = true
  pageError.value = ''
  try {
    const [menuItems, categoryList] = await Promise.all([listMenuItems(filters), listCategories()])
    items.value = menuItems
    categories.value = categoryList
  } catch (error) {
    pageError.value = apiError(error, 'Unable to load menu items.')
  } finally { loading.value = false }
}

function openCreate() { editingItem.value = null; formErrors.value = {}; showForm.value = true }
function openEdit(item) { editingItem.value = item; formErrors.value = {}; showForm.value = true }
function closeForm() { if (!saving.value) { showForm.value = false; editingItem.value = null; formErrors.value = {} } }

async function save(formData) {
  if (saving.value) return
  saving.value = true; formErrors.value = {}; success.value = ''
  try {
    if (editingItem.value) {
      await updateMenuItem(editingItem.value.id, formData)
      success.value = 'Menu item updated successfully.'
    } else {
      await createMenuItem(formData)
      success.value = 'Menu item created successfully.'
    }
    saving.value = false
    closeForm()
    await load()
  } catch (error) {
    formErrors.value = error.response?.data?.errors ?? { general: [apiError(error, 'Unable to save the menu item.')] }
    saving.value = false
  }
}

async function remove(item) {
  if (!window.confirm(`Delete “${item.name}”? This action cannot be undone.`)) return
  pageError.value = ''; success.value = ''
  try { await deleteMenuItem(item.id); items.value = items.value.filter(({ id }) => id !== item.id); success.value = 'Menu item deleted successfully.' }
  catch (error) { pageError.value = apiError(error, 'Unable to delete the menu item.') }
}

function categoryPosition(item) {
  const group = items.value.filter(({ category }) => category.id === item.category.id)
  return { index: group.findIndex(({ id }) => id === item.id), total: group.length }
}

async function move(item, offset) {
  if (!reorderEnabled.value || reordering.value) return
  const group = items.value.filter(({ category }) => category.id === item.category.id)
  const index = group.findIndex(({ id }) => id === item.id)
  const target = index + offset
  if (target < 0 || target >= group.length) return
  const previous = [...items.value]
  const firstIndex = items.value.findIndex(({ id }) => id === group[index].id)
  const secondIndex = items.value.findIndex(({ id }) => id === group[target].id)
  const reordered = [...items.value]
  ;[reordered[firstIndex], reordered[secondIndex]] = [reordered[secondIndex], reordered[firstIndex]]
  items.value = reordered; reordering.value = true; pageError.value = ''; success.value = ''
  const ids = [...group]; [ids[index], ids[target]] = [ids[target], ids[index]]
  try { await reorderMenuItems(item.category.id, ids.map(({ id }) => id)); success.value = 'Menu item order saved.' }
  catch (error) { items.value = previous; pageError.value = apiError(error, 'Unable to save item order.') }
  finally { reordering.value = false }
}

function resetFilters() { Object.assign(filters, { search: '', category_id: '', is_available: '', is_featured: '' }); load() }
onMounted(load)
</script>

<template>
  <section class="menu-items-page">
    <div class="page-heading"><div><p class="eyebrow">Restaurant menu</p><h1>Menu Items</h1><p>Create and organize the products offered by your restaurant.</p></div><button class="button" type="button" :disabled="categories.length === 0" @click="openCreate">Add item</button></div>
    <p v-if="success" class="success" role="status">{{ success }}</p>
    <div v-if="pageError" class="error-state" role="alert"><p>{{ pageError }}</p><button class="button button-secondary" type="button" @click="load">Try again</button></div>
    <div v-if="categories.length === 0 && !loading" class="notice">Create a category before adding menu items.</div>
    <form class="card filter-bar" @submit.prevent="load"><label>Search<input v-model.trim="filters.search" type="search" placeholder="Name or description"></label><label>Category<select v-model="filters.category_id"><option value="">All categories</option><option v-for="category in categories" :key="category.id" :value="category.id">{{ category.name }}</option></select></label><label>Availability<select v-model="filters.is_available"><option value="">All</option><option value="1">Available</option><option value="0">Unavailable</option></select></label><label>Featured<select v-model="filters.is_featured"><option value="">All</option><option value="1">Featured</option><option value="0">Not featured</option></select></label><div class="filter-actions"><button class="button" type="submit">Apply</button><button class="button button-secondary" type="button" @click="resetFilters">Reset</button></div></form>
    <p v-if="!reorderEnabled && items.length" class="reorder-note">Clear filters to reorder items.</p>
    <div v-if="showForm" class="card menu-item-form-card"><p v-if="formErrors.general" class="error">{{ formErrors.general[0] }}</p><MenuItemForm :item="editingItem" :categories="categories" :saving="saving" :server-errors="formErrors" @save="save" @cancel="closeForm" /></div>
    <div v-if="loading" class="card state-card" aria-live="polite">Loading menu items…</div>
    <div v-else-if="!pageError && items.length === 0" class="card state-card"><h2>No menu items found</h2><p>{{ categories.length ? 'Add an item or adjust your filters.' : 'Create a category first, then add your menu items.' }}</p><button v-if="categories.length" class="button" type="button" @click="openCreate">Add first item</button></div>
    <ul v-else-if="!pageError" class="menu-item-list" aria-label="Menu items"><li v-for="item in items" :key="item.id" class="card menu-item-card"><div class="menu-item-image"><img v-if="item.image_url" :src="item.image_url" :alt="item.name"><span v-else aria-hidden="true">No image</span></div><div class="menu-item-body"><div class="menu-item-title"><div><small>{{ item.category.name }}</small><h2>{{ item.name }}</h2></div><strong>{{ item.price }}</strong></div><p v-if="item.description">{{ item.description }}</p><div class="item-badges"><span :class="['status-badge', { inactive: !item.is_available }]">{{ item.is_available ? 'Available' : 'Unavailable' }}</span><span v-if="item.is_featured" class="status-badge featured">Featured</span></div><div class="category-actions"><button class="icon-button" type="button" :disabled="!reorderEnabled || categoryPosition(item).index === 0 || reordering" :aria-label="`Move ${item.name} up`" @click="move(item, -1)">↑</button><button class="icon-button" type="button" :disabled="!reorderEnabled || categoryPosition(item).index === categoryPosition(item).total - 1 || reordering" :aria-label="`Move ${item.name} down`" @click="move(item, 1)">↓</button><button class="button button-secondary" type="button" @click="openEdit(item)">Edit</button><button class="button button-danger" type="button" @click="remove(item)">Delete</button></div></div></li></ul>
  </section>
</template>
