<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckMaintenanceMode
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ($user->isAdmin() || $user->maintenance_bypass)) {
            return $next($request);
        }

        if ($this->isActive()) {
            return response()->json([
                'message'     => 'Service en maintenance.',
                'maintenance' => true,
            ], 503);
        }

        return $next($request);
    }

    public static function isActive(): bool
    {
        if ((bool) (Setting::find('maintenance.enabled')?->value ?? false)) {
            return true;
        }

        $startAt = Setting::find('maintenance.start_at')?->value;
        if (! $startAt) {
            return false;
        }

        $now   = Carbon::now();
        $start = Carbon::parse($startAt);
        $endAt = Setting::find('maintenance.end_at')?->value;
        $end   = $endAt ? Carbon::parse($endAt) : null;

        return $start->lte($now) && ($end === null || $end->gte($now));
    }
}
