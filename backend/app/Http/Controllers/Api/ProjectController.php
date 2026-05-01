<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Project\InviteMemberRequest;
use App\Http\Requests\Project\StoreProjectRequest;
use App\Http\Requests\Project\UpdateMemberRequest;
use App\Http\Requests\Project\UpdateProjectRequest;
use App\Http\Resources\ProjectResource;
use App\Http\Resources\UserResource;
use App\Jobs\RebuildKeywordIndex;
use App\Models\Project;
use App\Models\Scene;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ProjectController extends Controller
{
    public function index(Request $request): ResourceCollection
    {
        $this->authorize('viewAny', Project::class);

        $sceneBase = fn () => Scene::query()
            ->join('chapters', 'scenes.chapter_id', '=', 'chapters.id')
            ->join('arcs', 'chapters.arc_id', '=', 'arcs.id')
            ->whereColumn('arcs.project_id', 'projects.id');

        $projects = Project::where('owner_id', $request->user()->id)
            ->orWhereHas('members', fn ($q) => $q->where('user_id', $request->user()->id))
            ->with(['owner', 'members'])
            ->withCount('cards')
            ->addSelect([
                'projects.*',
                'word_count' => $sceneBase()->selectRaw('COALESCE(SUM(scenes.word_count), 0)'),
                'scene_count' => $sceneBase()->selectRaw('COUNT(*)'),
                'last_scene_title' => $sceneBase()
                    ->select('scenes.title')
                    ->orderByDesc('scenes.updated_at')
                    ->limit(1),
            ])
            ->orderByDesc('updated_at')
            ->get();

        return ProjectResource::collection($projects);
    }

    public function store(StoreProjectRequest $request): JsonResponse
    {
        $this->authorize('create', Project::class);

        $project = Project::create([
            ...$request->validated(),
            'owner_id' => $request->user()->id,
        ]);

        // Ajouter l'owner dans project_users
        $project->members()->attach($request->user()->id, ['role' => 'owner']);

        return (new ProjectResource($project->load('owner')))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Project $project): ProjectResource
    {
        $this->authorize('view', $project);

        return new ProjectResource($project->load('owner', 'members'));
    }

    public function update(UpdateProjectRequest $request, Project $project): ProjectResource
    {
        $this->authorize('update', $project);

        $project->update($request->validated());

        return new ProjectResource($project->load('owner'));
    }

    public function destroy(Project $project): JsonResponse
    {
        $this->authorize('delete', $project);

        $project->delete();

        return response()->json(null, 204);
    }

    // --- Membres ---

    public function members(Project $project): ResourceCollection
    {
        $this->authorize('view', $project);

        $members = $project->members()->paginate(15);

        return UserResource::collection($members);
    }

    public function inviteMember(InviteMemberRequest $request, Project $project): JsonResponse
    {
        $this->authorize('manageMembers', $project);

        $user = User::where('email', $request->email)->firstOrFail();

        if ($project->members()->where('user_id', $user->id)->exists()
            || $project->owner_id === $user->id) {
            return response()->json(['message' => 'Cet utilisateur est déjà membre du projet.'], 422);
        }

        $project->members()->attach($user->id, ['role' => $request->role]);

        return response()->json(['message' => 'Membre invité.'], 201);
    }

    public function updateMember(UpdateMemberRequest $request, Project $project, User $user): JsonResponse
    {
        $this->authorize('manageMembers', $project);

        $project->members()->updateExistingPivot($user->id, ['role' => $request->role]);

        return response()->json(['message' => 'Rôle mis à jour.']);
    }

    public function removeMember(Project $project, User $user): JsonResponse
    {
        $this->authorize('manageMembers', $project);

        $project->members()->detach($user->id);

        return response()->json(null, 204);
    }

    // --- Index mots-clés ---

    public function rebuildIndex(Project $project): JsonResponse
    {
        $this->authorize('rebuildIndex', $project);

        RebuildKeywordIndex::dispatch($project);

        return response()->json(['message' => 'Reconstruction de l\'index lancée.'], 202);
    }
}
