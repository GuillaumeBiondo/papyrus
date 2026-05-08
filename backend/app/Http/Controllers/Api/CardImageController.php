<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CardImageResource;
use App\Models\Card;
use App\Models\CardImage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CardImageController extends Controller
{
    private const ALLOWED_MIMES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    private const MAX_SIZE_KB   = 5120;

    public function index(Card $card): ResourceCollection
    {
        $this->authorize('view', $card);

        return CardImageResource::collection($card->images()->orderBy('created_at')->get());
    }

    public function store(Request $request, Card $card): JsonResponse
    {
        $this->authorize('update', $card);

        $request->validate([
            'image' => [
                'required',
                'file',
                'mimes:jpg,jpeg,png,gif,webp',
                'mimetypes:' . implode(',', self::ALLOWED_MIMES),
                'max:' . self::MAX_SIZE_KB,
            ],
        ]);

        $file        = $request->file('image');
        $storedName  = Str::uuid() . '.' . $file->extension();
        $path        = 'card-images/' . $card->id;

        Storage::disk('local')->putFileAs($path, $file, $storedName);

        $image = $card->images()->create([
            'original_name' => $file->getClientOriginalName(),
            'stored_name'   => $storedName,
            'mime_type'     => $file->getMimeType(),
            'size'          => $file->getSize(),
            'is_avatar'     => $card->images()->count() === 1,
        ]);

        return (new CardImageResource($image))
            ->response()
            ->setStatusCode(201);
    }

    public function destroy(Card $card, CardImage $image): JsonResponse
    {
        $this->authorize('update', $card);

        abort_if($image->card_id !== $card->id, 404);

        Storage::disk('local')->delete('card-images/' . $card->id . '/' . $image->stored_name);
        $wasAvatar = $image->is_avatar;
        $image->delete();

        if ($wasAvatar) {
            $next = $card->images()->orderBy('created_at')->first();
            $next?->update(['is_avatar' => true]);
        }

        return response()->json(null, 204);
    }

    public function setAvatar(Card $card, CardImage $image): CardImageResource
    {
        $this->authorize('update', $card);

        abort_if($image->card_id !== $card->id, 404);

        $card->images()->update(['is_avatar' => false]);
        $image->update(['is_avatar' => true]);

        return new CardImageResource($image);
    }

    public function serve(Card $card, CardImage $image): Response
    {
        $this->authorize('view', $card);

        abort_if($image->card_id !== $card->id, 404);

        $filePath = 'card-images/' . $card->id . '/' . $image->stored_name;

        abort_unless(Storage::disk('local')->exists($filePath), 404);

        return response(
            Storage::disk('local')->get($filePath),
            200,
            [
                'Content-Type'        => $image->mime_type,
                'Content-Disposition' => 'inline; filename="' . addslashes($image->original_name) . '"',
                'Cache-Control'       => 'private, max-age=3600',
            ]
        );
    }
}
