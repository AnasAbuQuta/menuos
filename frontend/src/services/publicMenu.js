import api from './api'

export async function getPublicMenu(slug) {
  const response = await api.get(`/public/menu/${encodeURIComponent(slug)}`)
  return response.data.data.menu
}
