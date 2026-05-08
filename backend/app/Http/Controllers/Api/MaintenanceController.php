<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Middleware\CheckMaintenanceMode;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MaintenanceController extends Controller
{
    public function status(Request $request): JsonResponse
    {
        $enabled        = (bool) (Setting::find('maintenance.enabled')?->value ?? false);
        $startAt        = Setting::find('maintenance.start_at')?->value;
        $endAt          = Setting::find('maintenance.end_at')?->value;
        $message        = Setting::find('maintenance.message')?->value
                            ?? 'Le service est temporairement en maintenance.';
        $warningMessage = Setting::find('maintenance.warning_message')?->value ?? '';

        $now   = Carbon::now();
        $start = $startAt ? Carbon::parse($startAt) : null;
        $end   = $endAt   ? Carbon::parse($endAt)   : null;

        $active  = CheckMaintenanceMode::isActive();
        $warning = ! $active && $start && $start->gt($now);

        $userExempt = false;
        if ($user = $request->user()) {
            $userExempt = $user->isAdmin() || (bool) $user->maintenance_bypass;
        }

        return response()->json([
            'active'          => $active,
            'warning'         => $warning,
            'start_at'        => $startAt,
            'end_at'          => $endAt,
            'message'         => $message,
            'warning_message' => $warningMessage,
            'user_exempt'     => $userExempt,
        ]);
    }
}
