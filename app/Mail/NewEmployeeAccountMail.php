<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewEmployeeAccountMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly User $employee,
        public readonly string $temporaryPassword,
        public readonly string $emailOtp,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Your SL Logistics employee account');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.new-employee-account');
    }
}
