import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const routes = [
  {
    path: '/',
    name: 'home',
    component: () => import('@/pages/HomePage.vue'),
    meta: { layout: 'blank' },
  },
  {
    path: '/login',
    name: 'login',
    component: () => import('@/pages/LoginPage.vue'),
    meta: { guest: true, layout: 'auth' },
  },
  {
    path: '/register',
    name: 'register',
    component: () => import('@/pages/RegisterPage.vue'),
    meta: { guest: true, layout: 'auth' },
  },
  {
    path: '/forgot-password',
    name: 'forgot-password',
    component: () => import('@/pages/ForgotPasswordPage.vue'),
    meta: { guest: true, layout: 'auth' },
  },
  {
    path: '/reset-password',
    name: 'reset-password',
    component: () => import('@/pages/ResetPasswordPage.vue'),
    meta: { layout: 'auth' },
  },
  {
    path: '/email-verified',
    name: 'email-verified',
    component: () => import('@/pages/EmailVerifiedPage.vue'),
    meta: { layout: 'auth' },
  },
  {
    path: '/play',
    name: 'play',
    component: () => import('@/pages/WorldMapPage.vue'),
    meta: { requiresAuth: true, layout: 'blank' },
  },
  {
    path: '/play/kingdom',
    name: 'kingdom',
    component: () => import('@/pages/CountryMapPage.vue'),
    meta: { requiresAuth: true, layout: 'blank' },
  },
  {
    path: '/play/town',
    name: 'town',
    component: () => import('@/pages/TownPage.vue'),
    meta: { requiresAuth: true, layout: 'blank' },
  },
  {
    path: '/play/seasons/:id',
    name: 'season-results',
    component: () => import('@/pages/SeasonResultsPage.vue'),
    meta: { requiresAuth: true, layout: 'blank' },
  },
  {
    path: '/admin',
    name: 'admin',
    component: () => import('@/pages/AdminPage.vue'),
    meta: { requiresAuth: true, layout: 'blank' },
  },
  {
    path: '/:pathMatch(.*)*',
    name: 'not-found',
    component: () => import('@/pages/NotFoundPage.vue'),
  },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

router.beforeEach((to) => {
  const auth = useAuthStore()

  if (to.meta.requiresAuth && !auth.isAuthenticated) {
    return { name: 'login', query: { redirect: to.fullPath } }
  }

  if (to.meta.guest && auth.isAuthenticated) {
    return { name: 'home' }
  }

  return true
})

export default router
