<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Todo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TodoController extends Controller
{
    public function update(Request $request, Todo $todo): JsonResponse
    {
        $project = $todo->arc?->project ?? $todo->chapter?->arc?->project;
        $this->authorize('update', $project);

        $data = $request->validate([
            'text'       => ['sometimes', 'string', 'max:500'],
            'is_done'    => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer'],
        ]);

        $todo->update($data);

        return response()->json(['data' => $todo]);
    }

    public function destroy(Todo $todo): JsonResponse
    {
        $project = $todo->arc?->project ?? $todo->chapter?->arc?->project;
        $this->authorize('delete', $project);

        $todo->delete();

        return response()->json(null, 204);
    }
}
