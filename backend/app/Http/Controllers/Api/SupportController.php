<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\SupportRequestMail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class SupportController extends Controller
{
    public function send(Request $request): JsonResponse
    {
        $data = $request->validate([
            'subject' => ['required', 'string', 'max:150'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        $user = $request->user();

        Mail::to('bot@guigeek.dev')->send(new SupportRequestMail(
            userName:  $user->name,
            userEmail: $user->email,
            subject:   $data['subject'],
            body:      $data['message'],
            sentAt:    now()->format('d/m/Y H:i:s'),
        ));

        return response()->json(['message' => 'Message envoyé.']);
    }
}
