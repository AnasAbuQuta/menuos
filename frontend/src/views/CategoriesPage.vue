<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import Sortable from 'sortablejs'
import AppIcon from '../components/ui/AppIcon.vue'
import { useI18n } from 'vue-i18n'
import CategoryForm from '../components/CategoryForm.vue'
import BaseConfirmDialog from '../components/ui/BaseConfirmDialog.vue'
import BaseEmptyState from '../components/ui/BaseEmptyState.vue'
import BaseIconButton from '../components/ui/BaseIconButton.vue'
import BaseLoading from '../components/ui/BaseLoading.vue'
import BaseToggle from '../components/ui/BaseToggle.vue'
import { apiError } from '../services/api'
import { createCategory, deleteCategory, listCategories, reorderCategories, updateCategory } from '../services/categories'
import { useToastStore } from '../stores/toast'
import { filterCategories, localizedValue, optimisticFieldUpdate, reorderByIds, secondaryLocalizedValue } from '../utils/management'

const { t, locale } = useI18n()
const toast = useToastStore()
const categories = ref([])
const search = ref('')
const listElement = ref(null)
const loading = ref(true)
const saving = ref(false)
const reordering = ref(false)
const toggling = ref(new Set())
const pageError = ref('')
const formError = ref('')
const pendingDelete = ref(null)
const deleting = ref(false)
const showForm = ref(false)
const editingCategory = ref(null)
let sortable

const filteredCategories = computed(() => filterCategories(categories.value, search.value))
const reorderDisabled = computed(() => Boolean(search.value.trim()) || saving.value || reordering.value)
const name = (category) => localizedValue(category, 'name', locale.value)
const secondaryName = (category) => secondaryLocalizedValue(category, 'name', locale.value)

async function load() {
  loading.value = true; pageError.value = ''
  try { categories.value = await listCategories() }
  catch (error) { pageError.value = apiError(error, t('management.categories.loadFailed')) }
  finally { loading.value = false; await nextTick(); setupSortable() }
}

function setupSortable() {
  sortable?.destroy()
  if (!listElement.value) return
  sortable = Sortable.create(listElement.value, {
    animation: 180, handle: '.drag-handle', draggable: '.category-row', ghostClass: 'sortable-ghost', chosenClass: 'sortable-chosen', dragClass: 'sortable-drag',
    disabled: reorderDisabled.value,
    onEnd: async () => {
      const ids = [...listElement.value.querySelectorAll('.category-row')].map((element) => Number(element.dataset.id))
      if (ids.every((id, index) => id === categories.value[index]?.id)) return
      const previous = [...categories.value]
      categories.value = reorderByIds(categories.value, ids)
      reordering.value = true
      try { categories.value = await reorderCategories(ids); toast.success(t('categories.ordered')) }
      catch (error) { categories.value = previous; toast.error(apiError(error, t('management.categories.reorderFailed'))) }
      finally { reordering.value = false; await nextTick(); setupSortable() }
    },
  })
}

watch(reorderDisabled, (disabled) => sortable?.option('disabled', disabled))
function openCreate() { editingCategory.value = null; formError.value = ''; showForm.value = true }
function openEdit(category) { editingCategory.value = category; formError.value = ''; showForm.value = true }
function closeForm() { if (!saving.value) { showForm.value = false; editingCategory.value = null; formError.value = '' } }

async function save(payload) {
  if (saving.value) return
  saving.value = true; formError.value = ''
  try {
    if (editingCategory.value) {
      const updated = await updateCategory(editingCategory.value.id, payload)
      categories.value = categories.value.map((category) => category.id === updated.id ? updated : category)
      toast.success(t('categories.updated'))
    } else { categories.value.push(await createCategory(payload)); toast.success(t('categories.created')) }
    saving.value = false; closeForm(); await nextTick(); setupSortable()
  } catch (error) { formError.value = apiError(error, t('management.categories.saveFailed')); toast.error(formError.value); saving.value = false }
}

async function toggleActive(category, value) {
  if (toggling.value.has(category.id)) return
  toggling.value.add(category.id)
  try {
    const updated = await optimisticFieldUpdate(category, 'is_active', value, () => updateCategory(category.id, { is_active: value }))
    Object.assign(category, updated)
    toast.success(t(value ? 'management.categories.activated' : 'management.categories.deactivated'))
  } catch (error) { toast.error(apiError(error, t('management.categories.saveFailed'))) }
  finally { toggling.value.delete(category.id) }
}

async function keyboardMove(category, offset) {
  if (reorderDisabled.value) return
  const index = categories.value.findIndex(({ id }) => id === category.id)
  const target = index + offset
  if (target < 0 || target >= categories.value.length) return
  const previous = [...categories.value]
  const reordered = [...categories.value]; [reordered[index], reordered[target]] = [reordered[target], reordered[index]]
  categories.value = reordered; reordering.value = true
  try { categories.value = await reorderCategories(reordered.map(({ id }) => id)); toast.success(t('categories.ordered')) }
  catch (error) { categories.value = previous; toast.error(apiError(error, t('management.categories.reorderFailed'))) }
  finally { reordering.value = false }
}

async function confirmRemove() {
  const category = pendingDelete.value
  if (!category || deleting.value) return
  deleting.value = true
  try { await deleteCategory(category.id); categories.value = categories.value.filter(({ id }) => id !== category.id); toast.success(t('categories.deleted')); pendingDelete.value = null }
  catch (error) { toast.error(apiError(error, t('management.categories.deleteFailed'))) }
  finally { deleting.value = false }
}

onMounted(load)
onBeforeUnmount(() => sortable?.destroy())
</script>

<template>
  <section class="categories-page">
    <div class="page-heading"><div><p class="eyebrow">{{ $t('categories.eyebrow') }}</p><h1>{{ $t('categories.title') }}</h1><p>{{ $t('categories.intro') }}</p></div><button class="button" type="button" @click="openCreate">{{ $t('categories.add') }}</button></div>
    <div class="management-search"><AppIcon name="search" :size="18" /><input v-model="search" type="search" :placeholder="$t('management.categories.search')" :aria-label="$t('management.categories.search')"><BaseIconButton v-if="search" :label="$t('management.categories.clear')" @click="search = ''"><AppIcon name="close" :size="18" /></BaseIconButton></div>
    <p v-if="search" class="reorder-note">{{ $t('management.categories.filteredReorder') }}</p>
    <div v-if="pageError" class="error-state" role="alert"><p>{{ pageError }}</p><button class="button button-secondary" type="button" @click="load">{{ $t('common.retry') }}</button></div>
    <div v-if="showForm" class="card category-form-card"><CategoryForm :category="editingCategory" :saving="saving" :server-error="formError" @save="save" @cancel="closeForm" /></div>
    <BaseLoading v-if="loading" :rows="4" :label="$t('common.loading')" />
    <BaseEmptyState v-else-if="!pageError && categories.length === 0" :title="$t('categories.empty')" :message="$t('categories.emptyHelp')"><button class="button" type="button" @click="openCreate">{{ $t('categories.first') }}</button></BaseEmptyState>
    <BaseEmptyState v-else-if="!pageError && filteredCategories.length === 0" :title="$t('management.categories.noResults')" :message="$t('management.categories.noResultsHelp')"><button class="button button-secondary" type="button" @click="search = ''">{{ $t('management.categories.clear') }}</button></BaseEmptyState>
    <ul v-else ref="listElement" class="category-list" :aria-label="$t('categories.title')">
      <li v-for="category in filteredCategories" :key="category.id" :data-id="category.id" class="card category-row">
        <BaseIconButton class="drag-handle" :label="$t('management.categories.drag', { name: name(category) })" :disabled="reorderDisabled"><AppIcon name="grip" :size="21" /></BaseIconButton>
        <div class="category-info"><strong>{{ name(category) }}</strong><small v-if="secondaryName(category)">{{ secondaryName(category) }}</small></div>
        <div class="category-actions">
          <BaseToggle :model-value="category.is_active" :label="$t(category.is_active ? 'management.categories.deactivate' : 'management.categories.activate', { name: name(category) })" :disabled="toggling.has(category.id)" @update:model-value="toggleActive(category, $event)" />
          <BaseIconButton :label="$t('categories.moveUp', { name: name(category) })" :disabled="reorderDisabled || categories[0]?.id === category.id" @click="keyboardMove(category, -1)"><AppIcon name="up" :size="18" /></BaseIconButton>
          <BaseIconButton :label="$t('categories.moveDown', { name: name(category) })" :disabled="reorderDisabled || categories.at(-1)?.id === category.id" @click="keyboardMove(category, 1)"><AppIcon name="down" :size="18" /></BaseIconButton>
          <BaseIconButton :label="$t('common.edit')" @click="openEdit(category)"><AppIcon name="edit" :size="18" /></BaseIconButton>
          <BaseIconButton danger :label="$t('common.delete')" @click="pendingDelete = category"><AppIcon name="trash" :size="18" /></BaseIconButton>
        </div>
      </li>
    </ul>
    <BaseConfirmDialog :open="Boolean(pendingDelete)" :title="$t('categories.deleteTitle')" :message="$t('categories.deleteMessage', { name: name(pendingDelete) })" :confirm-label="$t('common.delete')" danger :loading="deleting" @confirm="confirmRemove" @cancel="pendingDelete = null" />
  </section>
</template>
