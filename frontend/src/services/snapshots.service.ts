import api from './api'
import type { SceneSnapshot } from '@/types'

export const snapshotsService = {
  list(sceneId: string): Promise<{ snapshots: SceneSnapshot[] }> {
    return api.get(`/scenes/${sceneId}/snapshots`).then(r => r.data)
  },

  store(sceneId: string, payload: {
    content: string
    word_count: number
    word_delta: number
    trigger: 'auto' | 'manual'
    label?: string | null
  }): Promise<{ snapshot: SceneSnapshot }> {
    return api.post(`/scenes/${sceneId}/snapshots`, payload).then(r => r.data)
  },

  show(sceneId: string, snapshotId: number): Promise<{ snapshot: SceneSnapshot }> {
    return api.get(`/scenes/${sceneId}/snapshots/${snapshotId}`).then(r => r.data)
  },

  restore(sceneId: string, snapshotId: number): Promise<{ snapshot: SceneSnapshot; content: string }> {
    return api.post(`/scenes/${sceneId}/snapshots/${snapshotId}/restore`).then(r => r.data)
  },
}
