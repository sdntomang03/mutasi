<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\SerializesModels;

class ReciprocalMatchFound extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $teacherName,
        public string $matchedTeacherName,
        public string $originSudin,
        public string $destinationSudin,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Ada calon tukeran guru yang cocok');
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.reciprocal-match-found',
            with: [
                'teacherName' => $this->teacherName,
                'matchedTeacherName' => $this->matchedTeacherName,
                'originSudin' => $this->originSudin,
                'destinationSudin' => $this->destinationSudin,
            ],
        );
    }
}
