<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\VoiceUsageLog;
use App\Services\OpenAiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TranscriptionController extends Controller
{
    public function __construct(private readonly OpenAiService $openAi) {}

    public function transcribe(Request $request): JsonResponse
    {
        $request->validate([
            'audio'            => ['required', 'file', 'max:30720'],
            'duration_seconds' => ['nullable', 'numeric', 'min:0', 'max:300'],
            'source'           => ['nullable', 'string', 'max:50'],
        ]);

        $file  = $request->file('audio');
        $model = Setting::find('ai.openai_voice_model')?->value ?? 'whisper-1';

        try {
            $text = $this->openAi->transcribe($file->path(), 'audio/wav', $model);
        } catch (\RuntimeException $e) {
            Log::error('OpenAI Whisper error', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur du service de transcription.'], 502);
        }

        VoiceUsageLog::create([
            'user_id'       => $request->user()->id,
            'model'         => $model,
            'source'        => $request->input('source', 'notebook'),
            'audio_seconds' => (float) $request->input('duration_seconds', 0),
            'text_length'   => mb_strlen($text),
        ]);

        return response()->json(['text' => $text]);
    }
}
