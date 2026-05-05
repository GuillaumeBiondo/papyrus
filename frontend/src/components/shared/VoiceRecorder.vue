<script setup lang="ts">
import { ref, onUnmounted } from 'vue'
import { transcriptionService } from '@/services/transcription.service'

const CHUNK_SECONDS = 25
const MAX_SECONDS    = 120

const emit = defineEmits<{ chunk: [text: string] }>()

const isRecording    = ref(false)
const isTranscribing = ref(false)
const error          = ref<string | null>(null)
const elapsedSeconds = ref(0)

let mediaRecorder: MediaRecorder | null = null
let recordedChunks: Blob[] = []
let timerInterval: ReturnType<typeof setInterval> | null = null

function formatTime(s: number): string {
  const m = Math.floor(s / 60).toString().padStart(2, '0')
  return `${m}:${(s % 60).toString().padStart(2, '0')}`
}

function encodeWav(mono: Float32Array, sampleRate: number): Blob {
  const buf  = new ArrayBuffer(44 + mono.length * 2)
  const view = new DataView(buf)
  const str  = (off: number, s: string) => [...s].forEach((c, i) => view.setUint8(off + i, c.charCodeAt(0)))

  str(0,  'RIFF'); view.setUint32(4, 36 + mono.length * 2, true)
  str(8,  'WAVE')
  str(12, 'fmt '); view.setUint32(16, 16, true)
  view.setUint16(20, 1, true)               // PCM
  view.setUint16(22, 1, true)               // mono
  view.setUint32(24, sampleRate, true)
  view.setUint32(28, sampleRate * 2, true)  // byte rate
  view.setUint16(32, 2, true)               // block align
  view.setUint16(34, 16, true)              // bits/sample
  str(36, 'data'); view.setUint32(40, mono.length * 2, true)

  let off = 44
  for (const s of mono) {
    const c = Math.max(-1, Math.min(1, s))
    view.setInt16(off, c < 0 ? c * 0x8000 : c * 0x7fff, true)
    off += 2
  }
  return new Blob([buf], { type: 'audio/wav' })
}

const WHISPER_SAMPLE_RATE = 16_000

async function processAndTranscribe(blobs: Blob[]): Promise<void> {
  if (!blobs.length) return
  isTranscribing.value = true
  try {
    const raw         = new Blob(blobs, { type: blobs[0]?.type || 'audio/webm' })
    const arrayBuffer = await raw.arrayBuffer()

    // Decode to native sample rate
    const ctx      = new AudioContext()
    const decoded  = await ctx.decodeAudioData(arrayBuffer)
    await ctx.close()

    // Resample to 16 kHz (Whisper's native rate) — reduces file size 3×
    const targetLength  = Math.ceil(decoded.duration * WHISPER_SAMPLE_RATE)
    const offlineCtx    = new OfflineAudioContext(1, targetLength, WHISPER_SAMPLE_RATE)
    const source        = offlineCtx.createBufferSource()
    source.buffer       = decoded
    source.connect(offlineCtx.destination)
    source.start()
    const resampled     = await offlineCtx.startRendering()

    const mono            = resampled.getChannelData(0)
    const samplesPerChunk = CHUNK_SECONDS * WHISPER_SAMPLE_RATE

    for (let start = 0; start < mono.length; start += samplesPerChunk) {
      const wav  = encodeWav(mono.slice(start, start + samplesPerChunk), WHISPER_SAMPLE_RATE)
      const text = await transcriptionService.transcribe(wav)
      if (text) emit('chunk', text)
    }
  } catch (e: unknown) {
    const msg = (e as { response?: { data?: { message?: string } } })?.response?.data?.message
    error.value = msg ?? 'Erreur de transcription.'
  } finally {
    isTranscribing.value = false
  }
}

async function start(): Promise<void> {
  error.value = null
  try {
    const stream   = await navigator.mediaDevices.getUserMedia({ audio: true })
    const mimeType = MediaRecorder.isTypeSupported('audio/webm;codecs=opus')
      ? 'audio/webm;codecs=opus'
      : 'audio/webm'

    mediaRecorder   = new MediaRecorder(stream, { mimeType })
    recordedChunks  = []
    elapsedSeconds.value = 0
    isRecording.value    = true

    timerInterval = setInterval(() => {
      elapsedSeconds.value++
      if (elapsedSeconds.value >= MAX_SECONDS) stop()
    }, 1000)

    mediaRecorder.ondataavailable = (e) => { if (e.data.size) recordedChunks.push(e.data) }

    mediaRecorder.onstop = async () => {
      stream.getTracks().forEach((t) => t.stop())
      if (timerInterval) { clearInterval(timerInterval); timerInterval = null }
      await processAndTranscribe(recordedChunks)
      recordedChunks    = []
      isRecording.value = false
    }

    mediaRecorder.start()
  } catch {
    error.value = "Impossible d'accéder au microphone."
  }
}

function stop(): void {
  mediaRecorder?.stop()
}

onUnmounted(() => {
  if (timerInterval) clearInterval(timerInterval)
  if (mediaRecorder?.state === 'recording') mediaRecorder.stop()
})
</script>

<template>
  <div class="flex items-center gap-2">
    <button
      type="button"
      :title="isRecording ? 'Arrêter la dictée' : 'Dicter'"
      :disabled="isTranscribing && !isRecording"
      class="flex items-center justify-center w-7 h-7 rounded-full transition-colors disabled:opacity-40"
      :class="isRecording
        ? 'bg-red-500 hover:bg-red-600 text-white animate-pulse'
        : 'bg-gray-200 hover:bg-gray-300 dark:bg-gray-600 dark:hover:bg-gray-500 text-gray-700 dark:text-gray-200 shadow-sm'"
      @click="isRecording ? stop() : start()"
    >
      <svg v-if="!isRecording" class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24">
        <path d="M12 1a4 4 0 0 1 4 4v6a4 4 0 0 1-8 0V5a4 4 0 0 1 4-4zm0 2a2 2 0 0 0-2 2v6a2 2 0 0 0 4 0V5a2 2 0 0 0-2-2zm-1 15.93V21h2v-2.07A7.001 7.001 0 0 0 19 12h-2a5 5 0 0 1-10 0H5a7.001 7.001 0 0 0 6 6.93z"/>
      </svg>
      <svg v-else class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24">
        <rect x="4" y="4" width="16" height="16" rx="2"/>
      </svg>
    </button>

    <span v-if="isRecording" class="text-xs text-red-500 tabular-nums">
      {{ formatTime(elapsedSeconds) }}
    </span>
    <span v-else-if="isTranscribing" class="text-xs text-gray-400 dark:text-gray-500">
      Transcription…
    </span>

    <span v-if="error" class="text-xs text-red-500">{{ error }}</span>
  </div>
</template>
