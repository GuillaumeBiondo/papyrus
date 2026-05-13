<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AiEnrichType;
use App\Models\AiUsageLog;
use App\Models\Setting;
use App\Services\OpenAiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AiEnrichController extends Controller
{
    public function types(): JsonResponse
    {
        $types = AiEnrichType::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['id', 'type_key', 'label', 'description', 'sort_order', 'is_premium']);

        return response()->json(['types' => $types]);
    }

    public function enrich(Request $request): JsonResponse
    {
        $data = $request->validate([
            'type' => ['required', 'string', 'exists:ai_enrich_types,type_key'],
            'text' => ['required', 'string', 'max:300'],
        ]);

        $enrichType = AiEnrichType::where('type_key', $data['type'])
            ->where('is_active', true)
            ->first();

        if (! $enrichType) {
            return response()->json(['error' => 'Ce type d\'enrichissement est désactivé.'], 403);
        }

        $model   = Setting::find('ai.openai_model')?->value ?? 'gpt-4o-mini';
        $service = new OpenAiService();
        $items   = $service->enrich($enrichType->system_prompt, "« {$data['text']} »", $model);

        AiUsageLog::create([
            'user_id'             => $request->user()->id,
            'ai_verification_id'  => null,
            'verification_label'  => 'Dico: ' . $enrichType->label,
            'model'               => $model,
            'input_chars'         => mb_strlen($data['text']),
            'changes_count'       => min(count($items), 255),
        ]);

        return response()->json(['items' => $items]);
    }
}
