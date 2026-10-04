<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ReciprocalMatchFound extends Mailable
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
        return new Envelope(subject: 'Calon Tukeran Guru yang Cocok Telah Ditemukan');
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.reciprocal-match-found',
            with: [
                'teacherName' => $this->teacherName,
                'matchedTeacherName' => $this->matchedTeacherName,
                'originSudin' => $this->originSudin,
                'destinationSudin' => $this->destinationSudin,
                'dashboardUrl' => route('dashboard'),
            ],
        );
    }
}
