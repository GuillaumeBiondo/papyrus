<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiVerification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AiVerificationController extends Controller
{
    public function index(): JsonResponse
    {
        $verifications = AiVerification::orderBy('sort_order')->orderBy('id')->get();

        return response()->json(['verifications' => $verifications]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'label'                   => ['required', 'string', 'max:100'],
            'is_active'               => ['sometimes', 'boolean'],
            'target'                  => ['required', Rule::in(['selection', 'all', 'both'])],
            'has_extra_input'         => ['sometimes', 'boolean'],
            'extra_input_label'       => ['nullable', 'string', 'max:200'],
            'extra_input_placeholder' => ['nullable', 'string', 'max:500'],
            'pre_prompt'              => ['required', 'string'],
            'allowed_card_types'      => ['nullable', 'array'],
            'allowed_card_types.*'    => ['string', 'max:50'],
            'allow_multiple_cards'    => ['sometimes', 'boolean'],
        ]);

        $data['sort_order'] = AiVerification::max('sort_order') + 1;

        $verification = AiVerification::create($data);

        return response()->json(['verification' => $verification], 201);
    }

    public function update(Request $request, AiVerification $aiVerification): JsonResponse
    {
        $data = $request->validate([
            'label'                   => ['sometimes', 'string', 'max:100'],
            'is_active'               => ['sometimes', 'boolean'],
            'target'                  => ['sometimes', Rule::in(['selection', 'all', 'both'])],
            'has_extra_input'         => ['sometimes', 'boolean'],
            'extra_input_label'       => ['nullable', 'string', 'max:200'],
            'extra_input_placeholder' => ['nullable', 'string', 'max:500'],
            'pre_prompt'              => ['sometimes', 'string'],
            'sort_order'              => ['sometimes', 'integer', 'min:0'],
            'allowed_card_types'      => ['nullable', 'array'],
            'allowed_card_types.*'    => ['string', 'max:50'],
            'allow_multiple_cards'    => ['sometimes', 'boolean'],
        ]);

        $aiVerification->update($data);

        return response()->json(['verification' => $aiVerification->fresh()]);
    }

    public function destroy(AiVerification $aiVerification): JsonResponse
    {
        $aiVerification->delete();

        return response()->json(['success' => true]);
    }

    public function reorder(Request $request): JsonResponse
    {
        $request->validate([
            'order'   => ['required', 'array'],
            'order.*' => ['integer', 'exists:ai_verifications,id'],
        ]);

        foreach ($request->order as $position => $id) {
            AiVerification::where('id', $id)->update(['sort_order' => $position]);
        }

        return response()->json(['success' => true]);
    }
}
