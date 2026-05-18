<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Arc\StoreArcRequest;
use App\Http\Requests\Arc\UpdateArcRequest;
use App\Http\Resources\ArcResource;
use App\Models\Arc;
use App\Models\Project;
use App\Models\Setting;
use App\Models\Todo;
use App\Services\OpenAiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ArcController extends Controller
{
    public function index(Project $project): JsonResponse
    {
        $this->authorize('view', $project);

        $arcs = $project->arcs()->with('chapters.scenes')->get();

        return response()->json([
            'data' => ArcResource::collection($arcs),
        ]);
    }

    public function show(Arc $arc): JsonResponse
    {
        $this->authorize('view', $arc->project);

        return response()->json(['data' => new ArcResource($arc)]);
    }

    public function store(StoreArcRequest $request, Project $project): JsonResponse
    {
        $this->authorize('update', $project);

        $arc = $project->arcs()->create([
            ...$request->validated(),
            'order' => $request->input('order', $project->arcs()->count()),
        ]);

        return (new ArcResource($arc))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateArcRequest $request, Arc $arc): ArcResource
    {
        $this->authorize('update', $arc->project);

        $arc->update($request->validated());

        return new ArcResource($arc);
    }

    public function destroy(Arc $arc): JsonResponse
    {
        $this->authorize('delete', $arc->project);

        $arc->delete();

        return response()->json(null, 204);
    }

    public function reorder(Project $project): JsonResponse
    {
        $this->authorize('update', $project);

        foreach (request()->input('items', []) as $item) {
            Arc::where('id', $item['id'])->where('project_id', $project->id)
               ->update(['order' => $item['order']]);
        }

        return response()->json(['message' => 'Ordre mis à jour.']);
    }

    public function saveSummary(Request $request, Arc $arc): JsonResponse
    {
        $this->authorize('update', $arc->project);

        $data = $request->validate(['summary' => ['nullable', 'string', 'max:5000']]);
        $arc->update(['summary' => $data['summary']]);

        return response()->json(['summary' => $arc->summary]);
    }

    public function generateSummary(Arc $arc): JsonResponse
    {
        $this->authorize('update', $arc->project);

        $isPremium = (bool) (Setting::find('premium.summary_auto')?->value ?? false);
        if ($isPremium && ! request()->user()->isPremium()) {
            return response()->json(['error' => 'Fonctionnalité réservée aux comptes premium.'], 403);
        }

        // Collect all scene content in this arc
        $arc->load('chapters.scenes');
        $parts = [];
        foreach ($arc->chapters as $chapter) {
            foreach ($chapter->scenes as $scene) {
                if ($scene->content) {
                    $text = strip_tags($scene->content);
                    if (trim($text)) {
                        $parts[] = "### {$scene->title}\n{$text}";
                    }
                }
            }
        }

        if (empty($parts)) {
            return response()->json(['error' => 'Aucun contenu à résumer.'], 422);
        }

        $fullText = implode("\n\n", $parts);
        $model    = Setting::find('ai.openai_model')?->value ?? 'gpt-4o-mini';
        $service  = new OpenAiService();

        $systemPrompt = "Tu es un assistant littéraire. Génère un résumé concis (3 à 6 phrases) de l'arc narratif décrit ci-dessous. Le résumé doit capturer les événements clés, les arcs des personnages et les thèmes principaux. Réponds uniquement avec le résumé, sans introduction ni conclusion.";

        $summary = $service->summarize($systemPrompt, $fullText, $model);

        $arc->update(['summary' => $summary, 'summary_generated_at' => now()]);

        return response()->json([
            'summary'              => $arc->summary,
            'summary_generated_at' => $arc->summary_generated_at?->toIso8601String(),
        ]);
    }

    // ── Todos ─────────────────────────────────────────────────────

    public function todos(Arc $arc): JsonResponse
    {
        $this->authorize('view', $arc->project);

        return response()->json(['data' => $arc->todos()->get()]);
    }

    public function storeTodo(Request $request, Arc $arc): JsonResponse
    {
        $this->authorize('update', $arc->project);

        $data = $request->validate([
            'text'       => ['required', 'string', 'max:500'],
            'sort_order' => ['integer'],
        ]);

        $todo = $arc->todos()->create([
            'text'       => $data['text'],
            'sort_order' => $data['sort_order'] ?? $arc->todos()->count(),
        ]);

        return response()->json(['data' => $todo], 201);
    }
}
