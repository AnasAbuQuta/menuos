export function resolveAdminAccess(to, auth) {
  if (to.meta.requiresAuth && !auth.isAuthenticated) {
    return { name: 'login', query: { redirect: to.fullPath } }
  }

  if (to.meta.requiresAdmin && !auth.isSuperAdmin) return { name: 'unauthorized' }

  return null
}
