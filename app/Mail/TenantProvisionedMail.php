<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TenantProvisionedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $name,
        public string $domain,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'موقعك الإلكتروني جاهز للاستخدام! 🎉',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.tenant-provisioned',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
