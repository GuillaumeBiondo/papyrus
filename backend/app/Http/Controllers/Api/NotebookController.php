<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Notebook\StoreNotebookEntryRequest;
use App\Http\Requests\Notebook\UpdateNotebookEntryRequest;
use App\Http\Resources\NotebookEntryResource;
use App\Models\NotebookEntry;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class NotebookController extends Controller
{
    public function index(Request $request): ResourceCollection
    {
        $this->authorize('viewAny', NotebookEntry::class);

        $query = NotebookEntry::where('user_id', $request->user()->id);

        if ($request->filled('project_id')) {
            $query->where('project_id', $request->project_id);
        }

        if ($request->boolean('free')) {
            $query->whereNull('project_id');
        }

        return NotebookEntryResource::collection($query->paginate(15));
    }

    public function store(StoreNotebookEntryRequest $request): JsonResponse
    {
        $this->authorize('create', NotebookEntry::class);

        $entry = NotebookEntry::create([
            ...$request->validated(),
            'user_id' => $request->user()->id,
        ]);

        return (new NotebookEntryResource($entry))
            ->response()
            ->setStatusCode(201);
    }

    public function show(NotebookEntry $notebookEntry): NotebookEntryResource
    {
        $this->authorize('view', $notebookEntry);

        return new NotebookEntryResource($notebookEntry);
    }

    public function update(UpdateNotebookEntryRequest $request, NotebookEntry $notebookEntry): NotebookEntryResource
    {
        $this->authorize('update', $notebookEntry);

        $notebookEntry->update($request->validated());

        return new NotebookEntryResource($notebookEntry);
    }

    public function destroy(NotebookEntry $notebookEntry): JsonResponse
    {
        $this->authorize('delete', $notebookEntry);

        $notebookEntry->delete();

        return response()->json(null, 204);
    }

    public function transfer(Request $request, NotebookEntry $notebookEntry): JsonResponse
    {
        $this->authorize('update', $notebookEntry);

        $validated = $request->validate([
            'project_id' => ['required', 'uuid', 'exists:projects,id'],
        ]);

        $project = Project::findOrFail($validated['project_id']);

        $body = $notebookEntry->title
            ? $notebookEntry->title . "\n\n" . $notebookEntry->body
            : $notebookEntry->body;

        $project->notes()->create(['body' => $body]);

        $notebookEntry->delete();

        return response()->json(null, 204);
    }
}
