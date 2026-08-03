import api from './api'

export async function getPublicMenu(slug, language) {
  const response = await api.get(`/public/menu/${encodeURIComponent(slug)}`, { params: { lang: language } })
  return response.data.data.menu
}
