import api from './api'

export async function getRestaurant() {
  const { data } = await api.get('/restaurant')
  return data.data.restaurant
}

export async function updateRestaurant(payload) {
  const { data } = await api.put('/restaurant', payload)
  return data.data.restaurant
}

async function uploadImage(endpoint, file, onProgress) {
  const formData = new FormData()
  formData.append('image', file)
  const { data } = await api.post(`/restaurant/${endpoint}`, formData, {
    onUploadProgress: (event) => onProgress?.(event.total ? Math.round(event.loaded / event.total * 100) : 0),
  })
  return data.data.restaurant
}

export const uploadLogo = (file, onProgress) => uploadImage('logo', file, onProgress)
export const uploadCover = (file, onProgress) => uploadImage('cover', file, onProgress)

export async function deleteLogo() {
  const { data } = await api.delete('/restaurant/logo')
  return data.data.restaurant
}

export async function deleteCover() {
  const { data } = await api.delete('/restaurant/cover')
  return data.data.restaurant
}
