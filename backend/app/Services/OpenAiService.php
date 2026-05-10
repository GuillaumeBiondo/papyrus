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
}
