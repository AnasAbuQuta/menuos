import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import { join } from 'node:path'
import test from 'node:test'
import { fileURLToPath } from 'node:url'
import { resolveAdminAccess } from '../src/router/adminGuard.js'

const root = fileURLToPath(new URL('../', import.meta.url))
const read = (path) => readFileSync(join(root, path), 'utf8')

test('admin guard redirects unauthenticated and non-admin users safely', () => {
  const route = { fullPath: '/admin', meta: { requiresAuth: true, requiresAdmin: true } }
  assert.deepEqual(resolveAdminAccess(route, { isAuthenticated: false, isSuperAdmin: false }), { name: 'login', query: { redirect: '/admin' } })
  assert.deepEqual(resolveAdminAccess(route, { isAuthenticated: true, isSuperAdmin: false }), { name: 'unauthorized' })
  assert.equal(resolveAdminAccess(route, { isAuthenticated: true, isSuperAdmin: true }), null)
})

test('router exposes the isolated admin management routes', () => {
  const router = read('src/router/index.js')
  for (const routeName of ['admin-dashboard', 'admin-users', 'admin-user-detail', 'admin-restaurants', 'admin-restaurant-detail']) {
    assert.match(router, new RegExp(`name: '${routeName}'`))
  }
  assert.match(router, /requiresAdmin: true/)
  assert.match(router, /resolveAdminAccess/)
})

test('admin pages use the dedicated API service and confirmation dialogs', () => {
  const layout = read('src/layouts/AdminLayout.vue')
  const dashboard = read('src/views/admin/AdminDashboardPage.vue')
  const users = read('src/views/admin/AdminUsersPage.vue')
  const restaurants = read('src/views/admin/AdminRestaurantsPage.vue')
  const service = read('src/services/admin.js')
  assert.match(layout, /admin-dashboard/)
  assert.match(layout, /admin-users/)
  assert.match(layout, /admin-restaurants/)
  assert.match(dashboard, /admin-metrics/)
  assert.match(dashboard, /BaseEmptyState/)
  assert.match(users, /updateAdminUserStatus/)
  assert.match(users, /BaseConfirmDialog/)
  assert.match(users, /BaseEmptyState/)
  assert.match(restaurants, /updateAdminRestaurantStatus/)
  assert.match(restaurants, /BaseConfirmDialog/)
  assert.match(restaurants, /BaseEmptyState/)
  assert.match(service, /\/admin\/dashboard/)
  assert.match(service, /\/admin\/users/)
  assert.match(service, /\/admin\/restaurants/)
})

test('admin translations are complete in Arabic and English', () => {
  const en = JSON.parse(read('src/i18n/locales/en.json')).admin
  const ar = JSON.parse(read('src/i18n/locales/ar.json')).admin
  assert.deepEqual(Object.keys(ar).sort(), Object.keys(en).sort())
  assert.deepEqual(Object.keys(ar.metrics).sort(), Object.keys(en.metrics).sort())
  assert.notEqual(ar.dashboard, en.dashboard)
  assert.notEqual(ar.suspendRestaurantConfirm, en.suspendRestaurantConfirm)
})
