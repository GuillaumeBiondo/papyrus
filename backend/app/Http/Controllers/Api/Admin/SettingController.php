<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index(): JsonResponse
    {
        $settings = Setting::orderBy('group')->orderBy('key')->get();

        return response()->json(['settings' => $settings]);
    }

    public function update(Request $request, string $key): JsonResponse
    {
        $setting = Setting::findOrFail($key);

        $request->validate([
            'value' => ['present'],
        ]);

        $setting->update(['value' => $request->value]);

        return response()->json(['setting' => $setting->fresh()]);
    }
}
