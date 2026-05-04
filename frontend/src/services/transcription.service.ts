import api from './api'

export const transcriptionService = {
  transcribe: async (audioBlob: Blob): Promise<string> => {
    const form = new FormData()
    form.append('audio', audioBlob, 'audio.webm')
    const res = await api.post('/transcribe', form, {
      headers: { 'Content-Type': 'multipart/form-data' },
      timeout: 60_000,
    })
    return res.data.text ?? ''
  },
}
