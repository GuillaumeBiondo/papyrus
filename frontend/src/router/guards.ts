import type { Router } from 'vue-router'
import { useAuthStore } from '@/stores/auth.store'
import { useProjectsStore } from '@/stores/projects.store'

export function setupGuards(router: Router) {
  router.beforeEach(async (to) => {
    const auth = useAuthStore()

    if (to.meta.requiresAuth && !auth.isAuthenticated) {
      await auth.tryRestoreSession()
      if (!auth.isAuthenticated) return { name: 'login' }
    }

    if (to.meta.requiresProject && to.params.projectId) {
      const projects = useProjectsStore()
      const ok = await projects.checkAccess(to.params.projectId as string)
      if (!ok) return { name: 'dashboard' }
    }

    if (!to.meta.betaReader && auth.isBetaReader) {
      return { name: 'dashboard' }
    }
  })
}
