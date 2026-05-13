<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Chapter\ReorderChapterRequest;
use App\Http\Requests\Chapter\StoreChapterRequest;
use App\Http\Requests\Chapter\UpdateChapterRequest;
use App\Http\Resources\ChapterResource;
use App\Models\Arc;
use App\Models\Chapter;
use App\Models\Setting;
use App\Models\Todo;
use App\Services\OpenAiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChapterController extends Controller
{
    public function index(Arc $arc): JsonResponse
    {
        $this->authorize('view', $arc->project);

        $chapters = $arc->chapters()->with('scenes')->get();

        return response()->json(['data' => ChapterResource::collection($chapters)]);
    }

    public function show(Chapter $chapter): JsonResponse
    {
        $this->authorize('view', $chapter->arc->project);

        return response()->json(['data' => new ChapterResource($chapter)]);
    }

    public function store(StoreChapterRequest $request, Arc $arc): JsonResponse
    {
        $this->authorize('update', $arc->project);

        $chapter = $arc->chapters()->create([
            ...$request->validated(),
            'order' => $request->input('order', $arc->chapters()->count()),
        ]);

        return (new ChapterResource($chapter))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateChapterRequest $request, Chapter $chapter): ChapterResource
    {
        $this->authorize('update', $chapter->arc->project);

        $chapter->update($request->validated());

        return new ChapterResource($chapter);
    }

    public function destroy(Chapter $chapter): JsonResponse
    {
        $this->authorize('delete', $chapter->arc->project);

        $chapter->delete();

        return response()->json(null, 204);
    }

    public function reorder(ReorderChapterRequest $request): JsonResponse
    {
        foreach ($request->items as $item) {
            $data = ['order' => $item['order']];
            if (isset($item['arc_id'])) $data['arc_id'] = $item['arc_id'];
            Chapter::where('id', $item['id'])->update($data);
        }

        return response()->json(['message' => 'Ordre mis à jour.']);
    }

    public function saveSummary(Request $request, Chapter $chapter): JsonResponse
    {
        $this->authorize('update', $chapter->arc->project);

        $data = $request->validate(['summary' => ['nullable', 'string', 'max:5000']]);
        $chapter->update(['summary' => $data['summary']]);

        return response()->json(['summary' => $chapter->summary]);
    }

    public function generateSummary(Chapter $chapter): JsonResponse
    {
        $this->authorize('update', $chapter->arc->project);

        $isPremium = (bool) (Setting::find('premium.summary_auto')?->value ?? false);
        if ($isPremium && ! request()->user()->isPremium()) {
            return response()->json(['error' => 'Fonctionnalité réservée aux comptes premium.'], 403);
        }

        $chapter->load('scenes');
        $parts = [];
        foreach ($chapter->scenes as $scene) {
            if ($scene->content) {
                $text = strip_tags($scene->content);
                if (trim($text)) {
                    $parts[] = "### {$scene->title}\n{$text}";
                }
            }
        }

        if (empty($parts)) {
            return response()->json(['error' => 'Aucun contenu à résumer.'], 422);
        }

        $fullText = implode("\n\n", $parts);
        $model    = Setting::find('ai.openai_model')?->value ?? 'gpt-4o-mini';
        $service  = new OpenAiService();

        $systemPrompt = "Tu es un assistant littéraire. Génère un résumé concis (2 à 4 phrases) du chapitre décrit ci-dessous. Le résumé doit capturer les événements clés et l'évolution des personnages. Réponds uniquement avec le résumé, sans introduction ni conclusion.";

        $summary = $service->summarize($systemPrompt, $fullText, $model);

        $chapter->update(['summary' => $summary]);

        return response()->json(['summary' => $summary]);
    }

    // ── Todos ─────────────────────────────────────────────────────

    public function todos(Chapter $chapter): JsonResponse
    {
        $this->authorize('view', $chapter->arc->project);

        return response()->json(['data' => $chapter->todos()->get()]);
    }

    public function storeTodo(Request $request, Chapter $chapter): JsonResponse
    {
        $this->authorize('update', $chapter->arc->project);

        $data = $request->validate([
            'text'       => ['required', 'string', 'max:500'],
            'sort_order' => ['integer'],
        ]);

        $todo = $chapter->todos()->create([
            'text'       => $data['text'],
            'sort_order' => $data['sort_order'] ?? $chapter->todos()->count(),
        ]);

        return response()->json(['data' => $todo], 201);
    }
}
