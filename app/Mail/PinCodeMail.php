<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PinCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public string $pinCode, public string $userName)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Confirme sua conta - Classtrack');
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.pin-code');
    }
}