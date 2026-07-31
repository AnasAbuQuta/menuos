import api from './api'

export async function listCategories() {
  const { data } = await api.get('/categories')
  return data.data.categories
}

export async function createCategory(payload) {
  const { data } = await api.post('/categories', payload)
  return data.data.category
}

export async function getCategory(id) {
  const { data } = await api.get(`/categories/${id}`)
  return data.data.category
}

export async function updateCategory(id, payload) {
  const { data } = await api.put(`/categories/${id}`, payload)
  return data.data.category
}

export async function deleteCategory(id) {
  const { data } = await api.delete(`/categories/${id}`)
  return data
}

export async function reorderCategories(categoryIds) {
  const { data } = await api.post('/categories/reorder', { category_ids: categoryIds })
  return data.data.categories
}
