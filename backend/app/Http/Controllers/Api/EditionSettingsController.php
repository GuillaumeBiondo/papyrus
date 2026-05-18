<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectEditionSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EditionSettingsController extends Controller
{
    public function show(Project $project): JsonResponse
    {
        $this->authorize('view', $project);

        $settings = $project->editionSettings;

        return response()->json([
            'data' => $settings
                ? $settings->settings
                : ProjectEditionSettings::defaults(),
        ]);
    }

    public function update(Request $request, Project $project): JsonResponse
    {
        $this->authorize('update', $project);

        $incoming = $request->validate([
            'settings' => ['required', 'array'],
        ]);

        $current = $project->editionSettings?->settings ?? ProjectEditionSettings::defaults();

        $merged = array_replace_recursive($current, $incoming['settings']);

        $project->editionSettings()->updateOrCreate(
            ['project_id' => $project->id],
            ['settings'   => $merged],
        );

        return response()->json(['data' => $merged]);
    }
}
