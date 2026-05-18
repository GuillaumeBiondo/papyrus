<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\Workshop;
use Illuminate\Http\JsonResponse;

class AppConfigController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'snapshot_interval_words'  => (int) (Setting::find('snapshot_interval_words')?->value ?? 100),
            'premium_project_limit'    => (int) (Setting::find('premium.project_limit')?->value ?? 1),
            'summary_auto_is_premium'       => (bool) (Setting::find('premium.summary_auto')?->value ?? false),
            'edition_presets_is_premium'    => (bool) (Setting::find('premium.edition_presets')?->value ?? false),
            'edition_export_is_premium'     => (bool) (Setting::find('premium.edition_export')?->value ?? false),
            'workshops'                => Workshop::where('is_active', true)
                                            ->orderBy('sort_order')
                                            ->get(['key', 'label', 'description', 'is_premium', 'sort_order']),
        ]);
    }
}
