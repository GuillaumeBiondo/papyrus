import api from './api'
import type { Todo } from '@/types'

export const todosService = {
  async forArc(arcId: string): Promise<Todo[]> {
    const { data } = await api.get(`/arcs/${arcId}/todos`)
    return data.data
  },

  async forChapter(chapterId: string): Promise<Todo[]> {
    const { data } = await api.get(`/chapters/${chapterId}/todos`)
    return data.data
  },

  async createForArc(arcId: string, text: string): Promise<Todo> {
    const { data } = await api.post(`/arcs/${arcId}/todos`, { text })
    return data.data
  },

  async createForChapter(chapterId: string, text: string): Promise<Todo> {
    const { data } = await api.post(`/chapters/${chapterId}/todos`, { text })
    return data.data
  },

  async update(todoId: string, payload: Partial<Pick<Todo, 'text' | 'is_done' | 'sort_order'>>): Promise<Todo> {
    const { data } = await api.put(`/todos/${todoId}`, payload)
    return data.data
  },

  async destroy(todoId: string): Promise<void> {
    await api.delete(`/todos/${todoId}`)
  },
}
