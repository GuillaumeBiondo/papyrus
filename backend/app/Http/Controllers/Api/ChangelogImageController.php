<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class ChangelogImageController extends Controller
{
    private const ALLOWED_MIMES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

    public function serve(string $filename): Response
    {
        $filename = basename($filename);
        $path     = 'changelog-images/' . $filename;

        abort_if(! Storage::disk('local')->exists($path), 404);

        $mime = Storage::disk('local')->mimeType($path);

        return response(
            Storage::disk('local')->get($path),
            200,
            [
                'Content-Type'  => in_array($mime, self::ALLOWED_MIMES, true) ? $mime : 'image/jpeg',
                'Cache-Control' => 'private, max-age=86400',
            ]
        );
    }
}
