import api from './api'

export async function listMenuItems(filters = {}) {
  const params = Object.fromEntries(Object.entries(filters).filter(([, value]) => value !== '' && value != null))
  const { data } = await api.get('/menu-items', { params })
  return data.data.menu_items
}

export async function getMenuItem(id) {
  const { data } = await api.get(`/menu-items/${id}`)
  return data.data.menu_item
}

export async function createMenuItem(formData) {
  const { data } = await api.post('/menu-items', formData)
  return data.data.menu_item
}

export async function updateMenuItem(id, payload) {
  const isFormData = payload instanceof FormData
  if (isFormData) payload.set('_method', 'PUT')
  const { data } = isFormData
    ? await api.post(`/menu-items/${id}`, payload)
    : await api.put(`/menu-items/${id}`, payload)
  return data.data.menu_item
}

export async function deleteMenuItem(id) {
  const { data } = await api.delete(`/menu-items/${id}`)
  return data
}

export async function reorderMenuItems(categoryId, itemIds) {
  const { data } = await api.post('/menu-items/reorder', {
    category_id: categoryId,
    menu_item_ids: itemIds,
  })
  return data.data.menu_items
}
