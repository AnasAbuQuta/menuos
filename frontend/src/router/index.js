import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const AppShell = () => import('../layouts/AppShell.vue')
const LoginPage = () => import('../views/LoginPage.vue')
const RegisterPage = () => import('../views/RegisterPage.vue')
const RestaurantSetupPage = () => import('../views/RestaurantSetupPage.vue')
const DashboardPage = () => import('../views/DashboardPage.vue')
const CategoriesPage = () => import('../views/CategoriesPage.vue')
const MenuItemsPage = () => import('../views/MenuItemsPage.vue')
const RestaurantSettingsPage = () => import('../views/RestaurantSettingsPage.vue')
const PublicMenuPage = () => import('../views/PublicMenuPage.vue')
const QrCodePage = () => import('../views/QrCodePage.vue')
const NotFoundPage = () => import('../views/NotFoundPage.vue')
const UnauthorizedPage = () => import('../views/UnauthorizedPage.vue')
const NetworkErrorPage = () => import('../views/NetworkErrorPage.vue')

const router = createRouter({
  history: createWebHistory(),
  scrollBehavior: () => ({ top: 0 }),
  routes: [
    { path: '/menu/:slug', name: 'public-menu', component: PublicMenuPage },
    { path: '/', redirect: '/dashboard' },
    { path: '/login', name: 'login', component: LoginPage, meta: { guest: true } },
    { path: '/register', name: 'register', component: RegisterPage, meta: { guest: true } },
    { path: '/unauthorized', name: 'unauthorized', component: UnauthorizedPage },
    { path: '/network-error', name: 'network-error', component: NetworkErrorPage },
    { path: '/app', component: AppShell, meta: { requiresAuth: true }, children: [
      { path: 'restaurant/setup', name: 'restaurant-setup', component: RestaurantSetupPage },
      { path: 'dashboard', name: 'dashboard', component: DashboardPage },
    ] },
    { path: '/categories', component: AppShell, meta: { requiresAuth: true }, children: [{ path: '', name: 'categories', component: CategoriesPage }] },
    { path: '/restaurant', component: AppShell, meta: { requiresAuth: true }, children: [{ path: '', name: 'restaurant-settings', component: RestaurantSettingsPage }] },
    { path: '/qr-code', component: AppShell, meta: { requiresAuth: true }, children: [{ path: '', name: 'qr-code', component: QrCodePage }] },
    { path: '/menu-items', component: AppShell, meta: { requiresAuth: true }, children: [{ path: '', name: 'menu-items', component: MenuItemsPage }] },
    { path: '/dashboard', redirect: '/app/dashboard' },
    { path: '/:pathMatch(.*)*', name: 'not-found', component: NotFoundPage },
  ],
})

router.beforeEach(async (to) => {
  const auth = useAuthStore()
  await auth.restore()
  if (to.meta.requiresAuth && !auth.isAuthenticated) return { name: 'login', query: { redirect: to.fullPath } }
  if (to.meta.guest && auth.isAuthenticated) return { name: auth.hasRestaurant ? 'dashboard' : 'restaurant-setup' }
  if (to.meta.requiresAuth && auth.isAuthenticated && !auth.hasRestaurant && to.name !== 'restaurant-setup') return { name: 'restaurant-setup' }
  if (to.name === 'restaurant-setup' && auth.hasRestaurant) return { name: 'dashboard' }
})

export default router
