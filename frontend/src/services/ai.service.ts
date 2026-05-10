import api from './api'
import type { AiChange, AiVerification } from '@/types'

export const aiService = {
  async getVerifications(): Promise<{ verifications: AiVerification[] }> {
    const { data } = await api.get('/ai/verifications')
    return data
  },

  async verify(
    verificationId: number,
    text: string,
    extraInput?: string,
  ): Promise<{ changes: AiChange[] }> {
    const { data } = await api.post('/ai/verify', {
      verification_id: verificationId,
      text,
      extra_input: extraInput ?? null,
    })
    return data
  },
}
