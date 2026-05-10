<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiUsageLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class AiStatsController extends Controller
{
    // Pricing per 1M tokens (input side), approximate.
    // Output is typically 4× but we don't track it; we use input only as a proxy.
    private const COST_PER_M_TOKENS = [
        'gpt-4o-mini' => 0.15,
        'gpt-4o'      => 2.50,
        'gpt-4.1'     => 2.00,
        'gpt-4.1-mini'=> 0.40,
        'default'     => 0.15,
    ];

    public function index(): JsonResponse
    {
        // ── Per-revision stats ────────────────────────────────────
        $byRevision = AiUsageLog::select(
                'verification_label',
                DB::raw('COUNT(*) as calls'),
                DB::raw('SUM(input_chars) as total_chars'),
                DB::raw('AVG(input_chars) as avg_chars'),
                DB::raw('AVG(changes_count) as avg_changes'),
                DB::raw('MAX(created_at) as last_used_at'),
            )
            ->groupBy('verification_label')
            ->orderByDesc('calls')
            ->get()
            ->map(fn ($r) => [
                'label'        => $r->verification_label,
                'calls'        => (int) $r->calls,
                'total_chars'  => (int) $r->total_chars,
                'avg_chars'    => (int) round($r->avg_chars),
                'avg_changes'  => round($r->avg_changes, 1),
                'last_used_at' => $r->last_used_at,
                'est_tokens'   => (int) round($r->total_chars / 4),
            ]);

        // ── Per-user stats ────────────────────────────────────────
        $byUser = AiUsageLog::select(
                'ai_usage_logs.user_id',
                DB::raw('COUNT(*) as calls'),
                DB::raw('SUM(ai_usage_logs.input_chars) as total_chars'),
                DB::raw('MAX(ai_usage_logs.created_at) as last_used_at'),
            )
            ->join('users', 'users.id', '=', 'ai_usage_logs.user_id')
            ->addSelect('users.name', 'users.email')
            ->groupBy('ai_usage_logs.user_id', 'users.name', 'users.email')
            ->orderByDesc('calls')
            ->limit(50)
            ->get()
            ->map(fn ($r) => [
                'user_id'      => $r->user_id,
                'name'         => $r->name,
                'email'        => $r->email,
                'calls'        => (int) $r->calls,
                'total_chars'  => (int) $r->total_chars,
                'est_tokens'   => (int) round($r->total_chars / 4),
                'last_used_at' => $r->last_used_at,
            ]);

        // ── Global totals + cost estimate ────────────────────────
        $totals = AiUsageLog::select(
                DB::raw('COUNT(*) as calls'),
                DB::raw('SUM(input_chars) as total_chars'),
                'model',
            )
            ->groupBy('model')
            ->get();

        $totalCalls  = $totals->sum('calls');
        $totalChars  = $totals->sum('total_chars');
        $totalTokens = (int) round($totalChars / 4);

        $estimatedCost = $totals->sum(function ($row) {
            $rate = self::COST_PER_M_TOKENS[$row->model] ?? self::COST_PER_M_TOKENS['default'];
            return ($row->total_chars / 4 / 1_000_000) * $rate;
        });

        // ── Daily trend (last 30 days) ────────────────────────────
        $daily = AiUsageLog::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as calls'),
                DB::raw('SUM(input_chars) as total_chars'),
            )
            ->where('created_at', '>=', now()->subDays(29)->startOfDay())
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get()
            ->map(fn ($r) => [
                'date'  => $r->date,
                'calls' => (int) $r->calls,
            ]);

        return response()->json([
            'totals' => [
                'calls'          => (int) $totalCalls,
                'total_chars'    => (int) $totalChars,
                'est_tokens'     => $totalTokens,
                'estimated_cost' => round($estimatedCost, 4),
            ],
            'by_revision' => $byRevision,
            'by_user'     => $byUser,
            'daily'       => $daily,
        ]);
    }
}
