import axios from 'axios'

export const TOKEN_KEY = 'menuos_auth_token'

const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL || 'http://127.0.0.1:8000/api/v1',
  headers: { Accept: 'application/json' },
})

api.interceptors.request.use((config) => {
  const token = localStorage.getItem(TOKEN_KEY)

  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }

  return config
})

export function apiError(error, fallback = 'Something went wrong.') {
  const errors = error.response?.data?.errors
  if (errors) return Object.values(errors).flat()[0]
  return error.response?.data?.message || fallback
}

export default api
