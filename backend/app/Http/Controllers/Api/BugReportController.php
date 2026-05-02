<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\BugReportRequest;
use App\Mail\BugReportMail;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Mail;

class BugReportController extends Controller
{
    public function store(BugReportRequest $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->validated();

        try {
            Mail::to('bot@guigeek.dev')->send(new BugReportMail(
                userName:      $user->name,
                userEmail:     $user->email,
                message:       $data['message'],
                url:           $data['url'],
                apiCalls:      $data['api_calls'] ?? [],
                consoleErrors: $data['console_errors'] ?? [],
                reportedAt:    now()->format('d/m/Y H:i:s'),
            ));
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Erreur mail : ' . $e->getMessage(),
                'class'   => get_class($e),
            ], 500);
        }

        return response()->json(['message' => 'Rapport envoyé.']);
    }
}
