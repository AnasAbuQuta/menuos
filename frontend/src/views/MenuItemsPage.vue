<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue'
import Sortable from 'sortablejs'
import AppIcon from '../components/ui/AppIcon.vue'
import { useI18n } from 'vue-i18n'
import MenuItemForm from '../components/MenuItemForm.vue'
import BaseConfirmDialog from '../components/ui/BaseConfirmDialog.vue'
import BaseEmptyState from '../components/ui/BaseEmptyState.vue'
import BaseIconButton from '../components/ui/BaseIconButton.vue'
import BaseLoading from '../components/ui/BaseLoading.vue'
import BaseToggle from '../components/ui/BaseToggle.vue'
import { apiError } from '../services/api'
import { listCategories } from '../services/categories'
import { createMenuItem, deleteMenuItem, listMenuItems, reorderMenuItems, updateMenuItem } from '../services/menuItems'
import { useToastStore } from '../stores/toast'
import { localizedValue, optimisticFieldUpdate, secondaryLocalizedValue } from '../utils/management'
import { useLocalizedMeta } from '../composables/useLocalizedMeta'

const { t, locale } = useI18n()
useLocalizedMeta('meta.items')
const toast = useToastStore()
const items = ref([])
const categories = ref([])
const filters = reactive({ search: '', category_id: '', is_available: '', is_featured: '' })
const loading = ref(true)
const saving = ref(false)
const reordering = ref(false)
const toggling = ref(new Set())
const showForm = ref(false)
const editingItem = ref(null)
const pageError = ref('')
const formErrors = ref({})
const pendingDelete = ref(null)
const deleting = ref(false)
const listElement = ref(null)
let sortable

const reorderEnabled = computed(() => !filters.search && !filters.category_id && filters.is_available === '' && filters.is_featured === '' && !saving.value && !reordering.value)
const name = (record) => localizedValue(record, 'name', locale.value)
const secondaryName = (record) => secondaryLocalizedValue(record, 'name', locale.value)

async function load() {
  loading.value = true; pageError.value = ''
  try { [items.value, categories.value] = await Promise.all([listMenuItems(filters), listCategories()]) }
  catch (error) { pageError.value = apiError(error, t('management.items.loadFailed')) }
  finally { loading.value = false; await nextTick(); setupSortable() }
}

function setupSortable() {
  sortable?.destroy()
  if (!listElement.value) return
  sortable = Sortable.create(listElement.value, {
    animation: 180, handle: '.drag-handle', draggable: '.menu-item-card', ghostClass: 'sortable-ghost', chosenClass: 'sortable-chosen', dragClass: 'sortable-drag', disabled: !reorderEnabled.value,
    onEnd: async ({ oldIndex, newIndex, item }) => {
      if (oldIndex === newIndex) return
      const previous = [...items.value]
      const moved = previous.find(({ id }) => id === Number(item.dataset.id))
      const domIds = [...listElement.value.querySelectorAll('.menu-item-card')].map((element) => Number(element.dataset.id))
      const categoryIds = domIds.filter((id) => previous.find((entry) => entry.id === id)?.category.id === moved.category.id)
      const oldCategoryIds = previous.filter((entry) => entry.category.id === moved.category.id).map(({ id }) => id)
      if (categoryIds.length !== oldCategoryIds.length) { await nextTick(); setupSortable(); return }
      const byId = new Map(previous.map((entry) => [entry.id, entry]))
      let cursor = 0
      items.value = previous.map((entry) => entry.category.id === moved.category.id ? byId.get(categoryIds[cursor++]) : entry)
      reordering.value = true
      try { await reorderMenuItems(moved.category.id, categoryIds); toast.success(t('items.ordered')) }
      catch (error) { items.value = previous; toast.error(apiError(error, t('management.items.reorderFailed'))) }
      finally { reordering.value = false; await nextTick(); setupSortable() }
    },
  })
}

watch(reorderEnabled, (enabled) => sortable?.option('disabled', !enabled))
function openCreate() { editingItem.value = null; formErrors.value = {}; showForm.value = true }
function openEdit(item) { editingItem.value = item; formErrors.value = {}; showForm.value = true }
function closeForm() { if (!saving.value) { showForm.value = false; editingItem.value = null; formErrors.value = {} } }

async function save(formData) {
  if (saving.value) return
  saving.value = true; formErrors.value = {}
  try {
    const updated = editingItem.value ? await updateMenuItem(editingItem.value.id, formData) : await createMenuItem(formData)
    if (editingItem.value) items.value = items.value.map((item) => item.id === updated.id ? updated : item)
    else items.value.push(updated)
    toast.success(t(editingItem.value ? 'items.updated' : 'items.created'))
    saving.value = false; closeForm(); await nextTick(); setupSortable()
  } catch (error) { formErrors.value = error.response?.data?.errors ?? { general: [apiError(error, t('management.items.saveFailed'))] }; toast.error(apiError(error, t('management.items.saveFailed'))); saving.value = false }
}

async function toggle(item, field, value) {
  const key = `${item.id}:${field}`
  if (toggling.value.has(key)) return
  toggling.value.add(key)
  try {
    const updated = await optimisticFieldUpdate(item, field, value, () => updateMenuItem(item.id, { [field]: value }))
    Object.assign(item, updated)
    const message = field === 'is_featured' ? (value ? 'management.items.featured' : 'management.items.unfeatured') : (value ? 'management.items.available' : 'management.items.unavailable')
    toast.success(t(message))
  } catch (error) { toast.error(apiError(error, t('management.items.toggleFailed'))) }
  finally { toggling.value.delete(key) }
}

async function keyboardMove(item, offset) {
  if (!reorderEnabled.value) return
  const group = items.value.filter((entry) => entry.category.id === item.category.id)
  const index = group.findIndex(({ id }) => id === item.id)
  const target = index + offset
  if (target < 0 || target >= group.length) return
  const previous = [...items.value]
  const ids = group.map(({ id }) => id); [ids[index], ids[target]] = [ids[target], ids[index]]
  const byId = new Map(items.value.map((entry) => [entry.id, entry])); let cursor = 0
  items.value = items.value.map((entry) => entry.category.id === item.category.id ? byId.get(ids[cursor++]) : entry)
  reordering.value = true
  try { await reorderMenuItems(item.category.id, ids); toast.success(t('items.ordered')) }
  catch (error) { items.value = previous; toast.error(apiError(error, t('management.items.reorderFailed'))) }
  finally { reordering.value = false }
}

async function confirmRemove() {
  const item = pendingDelete.value
  if (!item || deleting.value) return
  deleting.value = true
  try { await deleteMenuItem(item.id); items.value = items.value.filter(({ id }) => id !== item.id); toast.success(t('items.deleted')); pendingDelete.value = null }
  catch (error) { toast.error(apiError(error, t('management.items.deleteFailed'))) }
  finally { deleting.value = false }
}

function resetFilters() { Object.assign(filters, { search: '', category_id: '', is_available: '', is_featured: '' }); load() }
onMounted(load)
onBeforeUnmount(() => sortable?.destroy())
</script>

<template>
  <section class="menu-items-page">
    <div class="page-heading"><div><p class="eyebrow">{{ $t('items.eyebrow') }}</p><h1>{{ $t('items.title') }}</h1><p>{{ $t('items.intro') }}</p></div><button class="button" type="button" :disabled="categories.length === 0" @click="openCreate">{{ $t('items.add') }}</button></div>
    <div v-if="pageError" class="error-state" role="alert"><p>{{ pageError }}</p><button class="button button-secondary" type="button" @click="load">{{ $t('common.retry') }}</button></div>
    <form class="card filter-bar" @submit.prevent="load"><label>{{ $t('items.search') }}<input v-model.trim="filters.search" type="search" :placeholder="$t('management.items.searchPlaceholder')"></label><label>{{ $t('items.category') }}<select v-model="filters.category_id"><option value="">{{ $t('items.allCategories') }}</option><option v-for="category in categories" :key="category.id" :value="category.id">{{ name(category) }}</option></select></label><label>{{ $t('items.availability') }}<select v-model="filters.is_available"><option value="">{{ $t('items.all') }}</option><option value="1">{{ $t('common.available') }}</option><option value="0">{{ $t('common.unavailable') }}</option></select></label><label>{{ $t('common.featured') }}<select v-model="filters.is_featured"><option value="">{{ $t('items.all') }}</option><option value="1">{{ $t('common.featured') }}</option><option value="0">{{ $t('management.items.notFeatured') }}</option></select></label><div class="filter-actions"><button class="button" type="submit">{{ $t('items.apply') }}</button><button class="button button-secondary" type="button" @click="resetFilters">{{ $t('items.reset') }}</button></div></form>
    <p v-if="!reorderEnabled && items.length" class="reorder-note">{{ $t('items.clearFilters') }}</p>
    <div v-if="showForm" class="card menu-item-form-card"><p v-if="formErrors.general" class="error">{{ formErrors.general[0] }}</p><MenuItemForm :item="editingItem" :categories="categories" :saving="saving" :server-errors="formErrors" @save="save" @cancel="closeForm" /></div>
    <BaseLoading v-if="loading" :rows="5" :label="$t('common.loading')" />
    <BaseEmptyState v-else-if="!pageError && items.length === 0" :title="$t('items.empty')" :message="categories.length ? $t('items.emptyHelp') : $t('items.categoryFirst')"><button v-if="categories.length" class="button" type="button" @click="openCreate">{{ $t('items.first') }}</button></BaseEmptyState>
    <ul v-else ref="listElement" class="menu-item-list" :aria-label="$t('items.title')">
      <li v-for="item in items" :key="item.id" :data-id="item.id" class="card menu-item-card">
        <BaseIconButton class="drag-handle" :label="$t('management.items.drag', { name: name(item) })" :disabled="!reorderEnabled"><AppIcon name="grip" :size="21" /></BaseIconButton>
        <div class="menu-item-image"><img v-if="item.image_url" :src="item.image_url" :alt="name(item)"><span v-else aria-hidden="true">{{ $t('common.noImage') }}</span></div>
        <div class="menu-item-body"><div class="menu-item-title"><div><small>{{ name(item.category) }}</small><h2>{{ name(item) }}</h2><small v-if="secondaryName(item)">{{ secondaryName(item) }}</small></div></div><p v-if="localizedValue(item, 'description', locale)">{{ localizedValue(item, 'description', locale) }}</p></div>
        <div class="menu-item-side">
          <strong class="menu-item-price">{{ item.price }}</strong><div class="management-actions">
            <BaseIconButton :active="item.is_featured" :label="$t(item.is_featured ? 'management.items.removeFeatured' : 'management.items.markFeatured')" :disabled="toggling.has(`${item.id}:is_featured`)" @click="toggle(item, 'is_featured', !item.is_featured)"><AppIcon name="star" :size="21" :filled="item.is_featured" /></BaseIconButton>
            <BaseToggle :model-value="item.is_available" :label="$t(item.is_available ? 'management.items.markUnavailable' : 'management.items.markAvailable', { name: name(item) })" :disabled="toggling.has(`${item.id}:is_available`)" @update:model-value="toggle(item, 'is_available', $event)" />
            <span class="status-label">{{ $t(item.is_available ? 'common.available' : 'common.unavailable') }}</span>
            <BaseIconButton :label="$t('categories.moveUp', { name: name(item) })" :disabled="!reorderEnabled" @click="keyboardMove(item, -1)"><AppIcon name="up" :size="18" /></BaseIconButton>
            <BaseIconButton :label="$t('categories.moveDown', { name: name(item) })" :disabled="!reorderEnabled" @click="keyboardMove(item, 1)"><AppIcon name="down" :size="18" /></BaseIconButton>
            <BaseIconButton :label="$t('common.edit')" @click="openEdit(item)"><AppIcon name="edit" :size="18" /></BaseIconButton>
            <BaseIconButton danger :label="$t('common.delete')" @click="pendingDelete = item"><AppIcon name="trash" :size="18" /></BaseIconButton>
          </div>
        </div>
      </li>
    </ul>
    <BaseConfirmDialog :open="Boolean(pendingDelete)" :title="$t('items.deleteTitle')" :message="$t('items.deleteMessage', { name: name(pendingDelete) })" :confirm-label="$t('common.delete')" danger :loading="deleting" @confirm="confirmRemove" @cancel="pendingDelete = null" />
  </section>
</template>
