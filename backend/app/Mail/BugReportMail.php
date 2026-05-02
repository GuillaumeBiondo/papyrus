<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BugReportMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $userName,
        public readonly string $userEmail,
        public readonly string $body,
        public readonly string $url,
        public readonly array  $apiCalls,
        public readonly array  $consoleErrors,
        public readonly string $reportedAt,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            to:      'bot@guigeek.dev',
            subject: "Bug sur Papyrus - {$this->userName} - {$this->reportedAt}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.bug_report',
        );
    }
}
