<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SetPasswordMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $setPasswordUrl;

    public function __construct(public User $user, string $token)
    {
        $this->setPasswordUrl = sprintf(
            '%s/set-password?token=%s&email=%s',
            rtrim(config('app.frontend_url'), '/'),
            $token,
            urlencode($user->email)
        );
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Set Password Akun Anda',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.set-password',
        );
    }
}
