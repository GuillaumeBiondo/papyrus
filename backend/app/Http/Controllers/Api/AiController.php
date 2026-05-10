<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AiUsageLog;
use App\Models\AiVerification;
use App\Models\Card;
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
            ->get(['id', 'label', 'target', 'has_extra_input', 'extra_input_label', 'extra_input_placeholder', 'allowed_card_types', 'allow_multiple_cards']);

        return response()->json(['verifications' => $verifications]);
    }

    public function verify(Request $request): JsonResponse
    {
        $data = $request->validate([
            'verification_id' => ['required', 'integer', 'exists:ai_verifications,id'],
            'text'            => ['required', 'string', 'max:50000'],
            'extra_input'     => ['nullable', 'string', 'max:2000'],
            'card_ids'        => ['nullable', 'array', 'max:10'],
            'card_ids.*'      => ['uuid'],
        ]);

        $verification = AiVerification::findOrFail($data['verification_id']);

        if (! $verification->is_active) {
            return response()->json(['error' => 'Cette vérification est désactivée.'], 403);
        }

        // Build card context if card IDs were provided
        $cardContext = '';
        if (!empty($data['card_ids'])) {
            $userId = $request->user()->id;
            $cards = Card::with('attributes')
                ->whereIn('id', $data['card_ids'])
                ->whereHas('project', function ($q) use ($userId) {
                    $q->where('owner_id', $userId)
                      ->orWhereHas('members', fn ($m) => $m->where('users.id', $userId));
                })
                ->get();

            foreach ($cards as $card) {
                $cardContext .= "\n[{$card->type}] {$card->title}\n";
                foreach ($card->attributes as $attr) {
                    $val = is_string($attr->value) ? $attr->value : json_encode($attr->value);
                    $val = strip_tags($val);
                    if ($val !== '' && $val !== 'null') {
                        $cardContext .= "- {$attr->key} : {$val}\n";
                    }
                }
            }
        }

        $model          = Setting::find('ai.openai_model')?->value ?? 'gpt-4o-mini';
        $responseFormat = Setting::find('ai.response_format')?->value ?? '';

        $service = new OpenAiService();

        $changes = $service->verify(
            $data['text'],
            $verification->pre_prompt,
            $responseFormat,
            $data['extra_input'] ?? null,
            $model,
            $cardContext
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
