<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Keyword\StoreKeywordRequest;
use App\Http\Resources\CardKeywordResource;
use App\Http\Resources\KeywordOccurrenceResource;
use App\Models\Card;
use App\Models\CardKeyword;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\ResourceCollection;

class KeywordController extends Controller
{
    public function index(Card $card): ResourceCollection
    {
        $this->authorize('view', $card);

        return CardKeywordResource::collection($card->keywords()->get());
    }

    public function store(StoreKeywordRequest $request, Card $card): JsonResponse
    {
        $this->authorize('update', $card);

        $keyword = $card->keywords()->create($request->validated());

        return (new CardKeywordResource($keyword))
            ->response()
            ->setStatusCode(201);
    }

    public function destroy(Card $card, CardKeyword $keyword): JsonResponse
    {
        $this->authorize('update', $card);

        $keyword->delete();

        return response()->json(null, 204);
    }

    public function occurrences(Card $card): ResourceCollection
    {
        $this->authorize('view', $card);

        $occurrences = \App\Models\KeywordOccurrence::whereHas('cardKeyword', fn ($q) =>
            $q->where('card_id', $card->id)
        )->with('scene')->paginate(50);

        return KeywordOccurrenceResource::collection($occurrences);
    }
}
