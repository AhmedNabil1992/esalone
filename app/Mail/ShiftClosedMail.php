<?php

namespace App\Mail;

use App\Models\Tenant\Shift;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ShiftClosedMail extends Mailable
{
    use Queueable, SerializesModels;

    public float $expected_cash;

    public float $discrepancy;

    public function __construct(
        public Shift $shift
    ) {
        $this->expected_cash = $shift->remain_amount;

        $this->discrepancy = (float) $shift->actual_amount - $this->expected_cash;
    }

    public function envelope(): Envelope
    {
        $subject = 'تقرير غلق الوردية رقم '.$this->shift->shift_no;
        if ($this->discrepancy < 0.0) {
            $subject .= ' 🔴 (عجز: '.number_format(abs($this->discrepancy), 2).' ج.م)';
        } elseif ($this->discrepancy > 0.0) {
            $subject .= ' 🟢 (زيادة: '.number_format($this->discrepancy, 2).' ج.م)';
        } else {
            $subject .= ' ✅ (مطابقة)';
        }

        return new Envelope(
            subject: $subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.shift-closed',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
