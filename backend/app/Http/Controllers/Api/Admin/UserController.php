<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    public function index(): JsonResponse
    {
        $users = User::select('users.*')
            ->withCount('ownedProjects as projects_count')
            ->selectSub(
                DB::table('scenes as s')
                    ->join('chapters as ch', 'ch.id', '=', 's.chapter_id')
                    ->join('arcs as a', 'a.id', '=', 'ch.arc_id')
                    ->join('projects as p', 'p.id', '=', 'a.project_id')
                    ->whereColumn('p.owner_id', 'users.id')
                    ->whereNull('s.deleted_at')
                    ->whereNull('p.deleted_at')
                    ->selectRaw('COALESCE(SUM(s.word_count), 0)'),
                'total_words'
            )
            ->selectSub(
                DB::table('arcs as a')
                    ->join('projects as p', 'p.id', '=', 'a.project_id')
                    ->whereColumn('p.owner_id', 'users.id')
                    ->whereNull('p.deleted_at')
                    ->selectRaw('COUNT(a.id)'),
                'arcs_count'
            )
            ->selectSub(
                DB::table('chapters as ch')
                    ->join('arcs as a', 'a.id', '=', 'ch.arc_id')
                    ->join('projects as p', 'p.id', '=', 'a.project_id')
                    ->whereColumn('p.owner_id', 'users.id')
                    ->whereNull('p.deleted_at')
                    ->selectRaw('COUNT(ch.id)'),
                'chapters_count'
            )
            ->selectSub(
                DB::table('scenes as s')
                    ->join('chapters as ch', 'ch.id', '=', 's.chapter_id')
                    ->join('arcs as a', 'a.id', '=', 'ch.arc_id')
                    ->join('projects as p', 'p.id', '=', 'a.project_id')
                    ->whereColumn('p.owner_id', 'users.id')
                    ->whereNull('s.deleted_at')
                    ->whereNull('p.deleted_at')
                    ->selectRaw('COUNT(s.id)'),
                'scenes_count'
            )
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function (User $user) {
                return [
                    'id'            => $user->id,
                    'name'          => $user->name,
                    'email'         => $user->email,
                    'role'          => $user->role,
                    'last_login_at' => $user->last_login_at?->toISOString(),
                    'created_at'    => $user->created_at->toISOString(),
                    'preferences'   => $user->preferences ?? [],
                    'projects_count' => (int) $user->projects_count,
                    'arcs_count'    => (int) $user->arcs_count,
                    'chapters_count' => (int) $user->chapters_count,
                    'scenes_count'  => (int) $user->scenes_count,
                    'total_words'   => (int) $user->total_words,
                    'avg_words_per_project' => $user->projects_count > 0
                        ? (int) round($user->total_words / $user->projects_count)
                        : 0,
                ];
            });

        return response()->json(['users' => $users]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'     => ['required', 'string', 'max:100'],
            'email'    => ['required', 'email', 'max:150', 'unique:users,email'],
            'password' => ['required', Password::min(8)],
        ]);

        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        return response()->json([
            'user' => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
                'role'  => $user->role,
            ],
        ], 201);
    }
}
