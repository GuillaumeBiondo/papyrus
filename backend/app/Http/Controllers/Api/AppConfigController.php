<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;

class AppConfigController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'snapshot_interval_words' => (int) (Setting::find('snapshot_interval_words')?->value ?? 100),
        ]);
    }
}
