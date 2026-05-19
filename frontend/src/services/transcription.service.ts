import api from './api'

export const transcriptionService = {
  transcribe: async (audioBlob: Blob, durationSeconds: number, source = 'notebook'): Promise<string> => {
    const form = new FormData()
    form.append('audio', audioBlob, 'audio.wav')
    form.append('duration_seconds', String(durationSeconds))
    form.append('source', source)
    const res = await api.post('/transcribe', form, {
      headers: { 'Content-Type': 'multipart/form-data' },
      timeout: 60_000,
    })
    return res.data.text ?? ''
  },
}
