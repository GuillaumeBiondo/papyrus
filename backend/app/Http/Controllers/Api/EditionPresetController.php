<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EditionPreset;
use App\Models\Setting;
use Illuminate\Http\Request;

class EditionPresetController extends Controller
{
    private function isPremiumGated(): bool
    {
        return (bool) (Setting::find('premium.edition_presets')?->value ?? false);
    }

    private function checkAccess(Request $request): void
    {
        if ($this->isPremiumGated() && ! $request->user()->effective_premium) {
            abort(403, 'Cette fonctionnalité est réservée aux comptes premium.');
        }
    }

    public function index(Request $request)
    {
        $this->checkAccess($request);

        return $request->user()
            ->editionPresets()
            ->orderBy('name')
            ->get(['id', 'name', 'settings', 'created_at']);
    }

    public function store(Request $request)
    {
        $this->checkAccess($request);

        $validated = $request->validate([
            'name'     => 'required|string|max:100',
            'settings' => 'required|array',
        ]);

        $preset = $request->user()->editionPresets()->create($validated);

        return response()->json($preset, 201);
    }

    public function update(Request $request, EditionPreset $editionPreset)
    {
        $this->checkAccess($request);

        abort_if($editionPreset->user_id !== $request->user()->id, 403);

        $validated = $request->validate([
            'name'     => 'sometimes|string|max:100',
            'settings' => 'sometimes|array',
        ]);

        $editionPreset->update($validated);

        return response()->json($editionPreset);
    }

    public function destroy(Request $request, EditionPreset $editionPreset)
    {
        $this->checkAccess($request);

        abort_if($editionPreset->user_id !== $request->user()->id, 403);

        $editionPreset->delete();

        return response()->noContent();
    }
}
