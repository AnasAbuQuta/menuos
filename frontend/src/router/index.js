import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import LoginPage from '../views/LoginPage.vue'
import RegisterPage from '../views/RegisterPage.vue'
import AppShell from '../layouts/AppShell.vue'
import RestaurantSetupPage from '../views/RestaurantSetupPage.vue'
import DashboardPage from '../views/DashboardPage.vue'
import CategoriesPage from '../views/CategoriesPage.vue'
import MenuItemsPage from '../views/MenuItemsPage.vue'

const router = createRouter({
  history: createWebHistory(),
  routes: [
    { path: '/', redirect: '/dashboard' },
    { path: '/login', name: 'login', component: LoginPage, meta: { guest: true } },
    { path: '/register', name: 'register', component: RegisterPage, meta: { guest: true } },
    {
      path: '/app', component: AppShell, meta: { requiresAuth: true },
      children: [
        { path: 'restaurant/setup', name: 'restaurant-setup', component: RestaurantSetupPage },
        { path: 'dashboard', name: 'dashboard', component: DashboardPage },
      ],
    },
    {
      path: '/categories', component: AppShell, meta: { requiresAuth: true },
      children: [{ path: '', name: 'categories', component: CategoriesPage }],
    },
    {
      path: '/menu-items', component: AppShell, meta: { requiresAuth: true },
      children: [{ path: '', name: 'menu-items', component: MenuItemsPage }],
    },
    { path: '/dashboard', redirect: '/app/dashboard' },
    { path: '/:pathMatch(.*)*', redirect: '/dashboard' },
  ],
})

router.beforeEach(async (to) => {
  const auth = useAuthStore()
  await auth.restore()

  if (to.meta.requiresAuth && !auth.isAuthenticated) return { name: 'login' }
  if (to.meta.guest && auth.isAuthenticated) {
    return { name: auth.hasRestaurant ? 'dashboard' : 'restaurant-setup' }
  }
  if (to.meta.requiresAuth && auth.isAuthenticated && !auth.hasRestaurant && to.name !== 'restaurant-setup') {
    return { name: 'restaurant-setup' }
  }
  if (to.name === 'restaurant-setup' && auth.hasRestaurant) return { name: 'dashboard' }
})

export default router
