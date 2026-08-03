import assert from 'node:assert/strict'
import test from 'node:test'
import { createPinia, setActivePinia } from 'pinia'
import { filterCategories, localizedValue, optimisticFieldUpdate, publicMenuUrl, reorderByIds } from '../src/utils/management.js'
import { useToastStore } from '../src/stores/toast.js'

const categories = [
  { id: 1, name_ar: 'مشروبات', name_en: 'Drinks' },
  { id: 2, name_ar: 'بيتزا', name_en: 'Pizza' },
  { id: 3, name_ar: null, name_en: 'Desserts', name: 'Desserts' },
]

test('category search matches partial Arabic and case-insensitive English names', () => {
  assert.deepEqual(filterCategories(categories, 'شرو').map(({ id }) => id), [1])
  assert.deepEqual(filterCategories(categories, 'zz').map(({ id }) => id), [2])
  assert.deepEqual(filterCategories(categories, 'DESS').map(({ id }) => id), [3])
  assert.deepEqual(filterCategories(categories, 'missing'), [])
})

test('localized content falls back to the other language', () => {
  assert.equal(localizedValue(categories[2], 'name', 'ar'), 'Desserts')
})

test('reorder helper follows the supplied identifier order', () => {
  assert.deepEqual(reorderByIds(categories, [3, 1, 2]).map(({ id }) => id), [3, 1, 2])
})

test('optimistic field update keeps success and rolls back failure', async () => {
  const item = { is_featured: false, is_available: true }
  await optimisticFieldUpdate(item, 'is_featured', true, async () => ({ is_featured: true }))
  assert.equal(item.is_featured, true)
  await assert.rejects(optimisticFieldUpdate(item, 'is_available', false, async () => { throw new Error('offline') }))
  assert.equal(item.is_available, true)
})

test('preview URL uses the supplied frontend origin', () => {
  assert.equal(publicMenuUrl('anas-restaurant', 'https://menu.example/'), 'https://menu.example/menu/anas-restaurant')
})

test('toast store records variants and suppresses duplicate messages', () => {
  globalThis.window = { setTimeout: () => 1 }
  setActivePinia(createPinia())
  const toast = useToastStore()
  toast.success('Saved')
  toast.success('Saved')
  toast.error('Failed')
  assert.equal(toast.messages.length, 2)
  assert.deepEqual(toast.messages.map(({ type }) => type), ['success', 'error'])
})
