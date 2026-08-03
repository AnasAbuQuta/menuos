import api from './api'

export async function getRestaurantQrCode() {
  const response = await api.get('/restaurant/qr-code')
  return response.data.data
}
