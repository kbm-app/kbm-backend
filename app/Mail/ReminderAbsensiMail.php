<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class ReminderAbsensiMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $appUrl;

    /** @var Collection<string, Collection<int, array<string, mixed>>> sesi dikelompokkan per tanggal (Y-m-d) */
    public Collection $sesiPerTanggal;

    /** @param Collection<int, array<string, mixed>> $sesi */
    public function __construct(public string $nama, Collection $sesi)
    {
        $this->sesiPerTanggal = $sesi->groupBy(fn ($s) => $s['tanggal']->toDateString());
        $this->appUrl         = rtrim(config('app.frontend_url'), '/').'/absensi';
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: sprintf('Pengingat: %d sesi belum diabsen', $this->sesiPerTanggal->flatten(1)->count()),
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.reminder-absensi');
    }
}
