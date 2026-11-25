<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $name,
        public string $code,
        public int $expiresIn,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Kode Verifikasi Email Anda');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.otp');
    }
}
