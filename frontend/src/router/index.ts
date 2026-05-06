import { createRouter, createWebHistory } from 'vue-router'
import { setupGuards } from './guards'

import AdminLayout from '@/layouts/AdminLayout.vue'
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
      path: '/admin',
      component: AdminLayout,
      meta: { requiresAuth: true, requiresAdmin: true },
      children: [
        { path: '', name: 'admin-dashboard', component: () => import('@/pages/admin/AdminDashboardPage.vue') },
        { path: 'users', name: 'admin-users', component: () => import('@/pages/admin/UsersPage.vue') },
        { path: 'content-types', name: 'admin-content-types', component: () => import('@/pages/admin/ContentTypesPage.vue') },
        { path: 'changelogs', name: 'admin-changelogs', component: () => import('@/pages/admin/ChangelogsPage.vue') },
        { path: 'settings', name: 'admin-settings', component: () => import('@/pages/admin/SettingsPage.vue') },
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
