<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class OpenAiService
{
    private string $apiKey;
    private string $baseUrl = 'https://api.openai.com/v1';

    public function __construct()
    {
        $this->apiKey = config('services.openai.key', '');
    }

    /**
     * @return array{originalText: string, suggestedText: string, explanation: string}[]
     */
    public function verify(
        string $text,
        string $prePrompt,
        string $responseFormat,
        ?string $extraInput = null,
        string $model = 'gpt-4o-mini',
        string $cardContext = ''
    ): array {
        if (empty($this->apiKey)) {
            throw new \RuntimeException('OpenAI API key is not configured (OPENAI_API_KEY missing).');
        }

        $systemContent = $prePrompt;
        if (!empty($extraInput)) {
            $systemContent .= "\n\nConsigne supplémentaire de l'utilisateur : " . $extraInput;
        }
        $systemContent .= "\n\n" . $responseFormat;

        $userContent = $text;
        if (!empty($cardContext)) {
            $userContent = "=== Contexte des fiches ===\n" . $cardContext . "\n=== Texte à analyser ===\n" . $text;
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type'  => 'application/json',
        ])->timeout(30)->post($this->baseUrl . '/chat/completions', [
            'model'           => $model,
            'messages'        => [
                ['role' => 'system', 'content' => $systemContent],
                ['role' => 'user',   'content' => $userContent],
            ],
            'response_format' => ['type' => 'json_object'],
            'temperature'     => 0.3,
        ]);

        if ($response->failed()) {
            throw new \RuntimeException('OpenAI API error: ' . $response->status() . ' ' . $response->body());
        }

        $content = $response->json('choices.0.message.content', '{}');
        $parsed  = json_decode($content, true);

        return $parsed['changes'] ?? [];
    }

    /**
     * @return array{text: string, detail: string}[]
     */
    public function enrich(
        string $systemPrompt,
        string $userPrompt,
        string $model = 'gpt-4o-mini'
    ): array {
        if (empty($this->apiKey)) {
            throw new \RuntimeException('OpenAI API key is not configured (OPENAI_API_KEY missing).');
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type'  => 'application/json',
        ])->timeout(20)->post($this->baseUrl . '/chat/completions', [
            'model'           => $model,
            'messages'        => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user',   'content' => $userPrompt],
            ],
            'response_format' => ['type' => 'json_object'],
            'temperature'     => 0.7,
        ]);

        if ($response->failed()) {
            throw new \RuntimeException('OpenAI API error: ' . $response->status() . ' ' . $response->body());
        }

        $content = $response->json('choices.0.message.content', '{}');
        $parsed  = json_decode($content, true);

        return $parsed['items'] ?? [];
    }

    public function transcribe(
        string $filePath,
        string $mimeType,
        string $model = 'whisper-1',
        string $language = 'fr'
    ): string {
        if (empty($this->apiKey)) {
            throw new \RuntimeException('OpenAI API key is not configured (OPENAI_API_KEY missing).');
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
        ])->timeout(60)->attach(
            'file',
            file_get_contents($filePath),
            'audio.wav',
            ['Content-Type' => $mimeType]
        )->post($this->baseUrl . '/audio/transcriptions', [
            'model'    => $model,
            'language' => $language,
        ]);

        if ($response->failed()) {
            throw new \RuntimeException('OpenAI Whisper error: ' . $response->status() . ' ' . $response->body());
        }

        return trim($response->json('text', ''));
    }

    public function summarize(
        string $systemPrompt,
        string $userPrompt,
        string $model = 'gpt-4o-mini',
        int $maxTokens = 400,
        float $temperature = 0.5
    ): string {
        if (empty($this->apiKey)) {
            throw new \RuntimeException('OpenAI API key is not configured (OPENAI_API_KEY missing).');
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type'  => 'application/json',
        ])->timeout(30)->post($this->baseUrl . '/chat/completions', [
            'model'       => $model,
            'messages'    => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user',   'content' => $userPrompt],
            ],
            'temperature' => $temperature,
            'max_tokens'  => $maxTokens,
        ]);

        if ($response->failed()) {
            throw new \RuntimeException('OpenAI API error: ' . $response->status() . ' ' . $response->body());
        }

        return trim($response->json('choices.0.message.content', ''));
    }
}
