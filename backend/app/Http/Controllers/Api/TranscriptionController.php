<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TranscriptionController extends Controller
{
    public function transcribe(Request $request): JsonResponse
    {
        $request->validate([
            'audio' => ['required', 'file', 'max:30720'],
        ]);

        $file   = $request->file('audio');
        $config = config('services.whisper');

        try {
            $response = Http::timeout(60)
                ->connectTimeout(15)
                ->withToken($config['token'])
                ->attach('file', file_get_contents($file->path()), 'audio.wav', ['Content-Type' => 'audio/wav'])
                ->post("{$config['url']}/v1/audio/transcriptions", [
                    'model'    => $config['model'],
                    'language' => 'fr',
                ]);
        } catch (ConnectionException $e) {
            Log::error('Whisper STT unreachable', ['url' => $config['url'], 'error' => $e->getMessage()]);
            return response()->json(['message' => 'Service de transcription inaccessible.'], 503);
        }

        if (! $response->successful()) {
            Log::error('Whisper STT error', [
                'status' => $response->status(),
                'body'   => substr($response->body(), 0, 500),
            ]);
            return response()->json(['message' => 'Erreur du service de transcription.'], 502);
        }

        $text = $response->json('text', '');

        return response()->json(['text' => trim($text)]);
    }
}
