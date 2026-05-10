<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AiUsageLog;
use App\Models\AiVerification;
use App\Models\Setting;
use App\Services\OpenAiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AiController extends Controller
{
    public function verifications(): JsonResponse
    {
        $verifications = AiVerification::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['id', 'label', 'target', 'has_extra_input', 'extra_input_label', 'extra_input_placeholder']);

        return response()->json(['verifications' => $verifications]);
    }

    public function verify(Request $request): JsonResponse
    {
        $data = $request->validate([
            'verification_id' => ['required', 'integer', 'exists:ai_verifications,id'],
            'text'            => ['required', 'string', 'max:50000'],
            'extra_input'     => ['nullable', 'string', 'max:2000'],
        ]);

        $verification = AiVerification::findOrFail($data['verification_id']);

        if (! $verification->is_active) {
            return response()->json(['error' => 'Cette vérification est désactivée.'], 403);
        }

        $model          = Setting::find('ai.openai_model')?->value ?? 'gpt-4o-mini';
        $responseFormat = Setting::find('ai.response_format')?->value ?? '';

        $service = new OpenAiService();

        $changes = $service->verify(
            $data['text'],
            $verification->pre_prompt,
            $responseFormat,
            $data['extra_input'] ?? null,
            $model
        );

        AiUsageLog::create([
            'user_id'             => $request->user()->id,
            'ai_verification_id'  => $verification->id,
            'verification_label'  => $verification->label,
            'model'               => $model,
            'input_chars'         => mb_strlen($data['text']),
            'changes_count'       => count($changes),
        ]);

        return response()->json(['changes' => $changes]);
    }
}
