<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EditionDocument;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EditionDocumentController extends Controller
{
    // Retourne le catalogue complet fusionné avec les documents existants du projet
    public function index(Project $project): JsonResponse
    {
        $this->authorize('view', $project);

        $existing = $project->editionDocuments()->get()->keyBy('type');

        $documents = collect(EditionDocument::TYPES)->map(function (array $def) use ($existing) {
            $doc = $existing->get($def['key']);

            return [
                'id'         => $doc?->id,
                'type'       => $def['key'],
                'label'      => $def['label'],
                'category'   => $def['category'],
                'sort_order' => $doc?->sort_order ?? $def['sort_order'],
                'title'      => $doc?->title,
                'is_enabled' => $doc?->is_enabled ?? false,
                'updated_at' => $doc?->updated_at?->toIso8601String(),
            ];
        });

        return response()->json(['data' => $documents->values()]);
    }

    // Active (ou désactive) un type de document — crée l'enregistrement si nécessaire
    public function toggle(Request $request, Project $project): JsonResponse
    {
        $this->authorize('update', $project);

        $validKeys = collect(EditionDocument::TYPES)->pluck('key')->toArray();

        $data = $request->validate([
            'type'       => ['required', 'string', Rule::in($validKeys)],
            'is_enabled' => ['required', 'boolean'],
        ]);

        $def = collect(EditionDocument::TYPES)->firstWhere('key', $data['type']);

        $doc = $project->editionDocuments()->updateOrCreate(
            ['type' => $data['type']],
            ['is_enabled' => $data['is_enabled'], 'sort_order' => $def['sort_order']],
        );

        return response()->json(['data' => [
            'id'         => $doc->id,
            'type'       => $doc->type,
            'is_enabled' => $doc->is_enabled,
            'updated_at' => $doc->updated_at->toIso8601String(),
        ]]);
    }

    // Retourne un document avec son contenu complet
    public function show(EditionDocument $document): JsonResponse
    {
        $this->authorize('view', $document->project);

        return response()->json(['data' => [
            'id'         => $document->id,
            'type'       => $document->type,
            'title'      => $document->title,
            'content'    => $document->content,
            'is_enabled' => $document->is_enabled,
            'sort_order' => $document->sort_order,
            'updated_at' => $document->updated_at->toIso8601String(),
        ]]);
    }

    // Met à jour le contenu ou le titre d'un document existant
    public function update(Request $request, EditionDocument $document): JsonResponse
    {
        $this->authorize('update', $document->project);

        $data = $request->validate([
            'title'      => ['nullable', 'string', 'max:255'],
            'content'    => ['nullable', 'string'],
            'sort_order' => ['integer'],
        ]);

        $document->update(array_filter($data, fn ($v) => $v !== null));

        return response()->json(['data' => [
            'id'         => $document->id,
            'type'       => $document->type,
            'title'      => $document->title,
            'content'    => $document->content,
            'sort_order' => $document->sort_order,
            'updated_at' => $document->updated_at->toIso8601String(),
        ]]);
    }
}
