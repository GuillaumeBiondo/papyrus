import { createRouter, createWebHistory } from 'vue-router'
import { setupGuards } from './guards'

import AppLayout from '@/layouts/AppLayout.vue'
import AuthLayout from '@/layouts/AuthLayout.vue'
import BetaLayout from '@/layouts/BetaLayout.vue'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/login',
      component: AuthLayout,
      children: [{ path: '', name: 'login', component: () => import('@/pages/auth/LoginPage.vue') }],
    },
    {
      path: '/',
      component: AppLayout,
      meta: { requiresAuth: true },
      children: [
        { path: '', redirect: '/dashboard' },
        {
          path: 'dashboard',
          name: 'dashboard',
          component: () => import('@/pages/dashboard/DashboardPage.vue'),
        },
        {
          path: 'notebook',
          name: 'notebook',
          component: () => import('@/pages/notebook/NotebookPage.vue'),
        },
        {
          path: 'profile',
          name: 'profile',
          component: () => import('@/pages/profile/ProfilePage.vue'),
        },
        {
          path: 'settings',
          name: 'settings',
          component: () => import('@/pages/settings/SettingsPage.vue'),
        },
        {
          path: 'projects/:projectId',
          meta: { requiresProject: true },
          children: [
            {
              path: 'edit',
              name: 'editor',
              component: () => import('@/pages/editor/EditorPage.vue'),
            },
            {
              path: 'cards',
              name: 'cards',
              component: () => import('@/pages/cards/CardsPage.vue'),
            },
          ],
        },
      ],
    },
    {
      path: '/beta/:token',
      component: BetaLayout,
      name: 'beta-reader',
      meta: { betaReader: true },
    },
  ],
})

setupGuards(router)

export default router
