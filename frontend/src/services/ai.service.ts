import api from './api'
import type { AiChange, AiEnrichType, AiVerification } from '@/types'

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

  async getEnrichTypes(): Promise<{ types: Pick<AiEnrichType, 'id' | 'type_key' | 'label' | 'description' | 'sort_order' | 'is_premium'>[] }> {
    const { data } = await api.get('/ai/enrich-types')
    return data
  },

  async enrich(
    type: string,
    text: string,
  ): Promise<{ items: { text: string; detail: string }[] }> {
    const { data } = await api.post('/ai/enrich', { type, text })
    return data
  },
}
