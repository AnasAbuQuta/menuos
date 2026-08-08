import api from './api'

export const getAdminDashboard = async () => (await api.get('/admin/dashboard')).data.data
export const getAdminUsers = async (params) => (await api.get('/admin/users', { params })).data.data
export const getAdminUser = async (id) => (await api.get(`/admin/users/${id}`)).data.data.user
export const updateAdminUserStatus = async (id, status) => (await api.patch(`/admin/users/${id}/status`, { status })).data.data.user
export const getAdminRestaurants = async (params) => (await api.get('/admin/restaurants', { params })).data.data
export const getAdminRestaurant = async (id) => (await api.get(`/admin/restaurants/${id}`)).data.data.restaurant
export const updateAdminRestaurantStatus = async (id, status) => (await api.patch(`/admin/restaurants/${id}/status`, { status })).data.data.restaurant
