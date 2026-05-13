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
    cardIds?: string[],
  ): Promise<{ changes: AiChange[] }> {
    const { data } = await api.post('/ai/verify', {
      verification_id: verificationId,
      text,
      extra_input: extraInput ?? null,
      card_ids: cardIds?.length ? cardIds : null,
    })
    return data
  },

  async enrich(
    type: 'definition' | 'synonymes' | 'metaphores' | 'champ_lexical' | 'registre',
    text: string,
  ): Promise<{ items: { text: string; detail: string }[] }> {
    const { data } = await api.post('/ai/enrich', { type, text })
    return data
  },
}
