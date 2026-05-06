<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(): JsonResponse
    {
        $totalUsers    = User::where('role', 'user')->count();
        $totalAdmins   = User::where('role', 'admin')->count();
        $totalProjects = Project::count();

        $totalWords = DB::table('scenes')
            ->whereNull('deleted_at')
            ->sum('word_count');

        $newUsersThisWeek = User::where('role', 'user')
            ->where('created_at', '>=', now()->startOfWeek())
            ->count();

        $activeThisWeek = User::where('role', 'user')
            ->where('last_login_at', '>=', now()->subWeek())
            ->count();

        $recentUsers = User::where('role', 'user')
            ->select('id', 'name', 'email', 'created_at', 'last_login_at')
            ->latest()
            ->limit(5)
            ->get();

        return response()->json([
            'stats' => [
                'total_users'        => $totalUsers,
                'total_admins'       => $totalAdmins,
                'total_projects'     => $totalProjects,
                'total_words'        => (int) $totalWords,
                'new_users_week'     => $newUsersThisWeek,
                'active_users_week'  => $activeThisWeek,
            ],
            'recent_users' => $recentUsers,
        ]);
    }
}
