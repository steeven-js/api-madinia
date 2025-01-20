<?php

namespace App\Mail;

use App\Models\EventOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RefundConfirmation extends Mailable
{
    use Queueable, SerializesModels;

    public EventOrder $order;
    public string $refundId;

    /**
     * Create a new message instance.
     */
    public function __construct(EventOrder $order, string $refundId)
    {
        $this->order = $order;
        $this->refundId = $refundId;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Confirmation de remboursement - Madin.IA',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.refund-confirmation',
            with: [
                'order' => $this->order,
                'refundId' => $this->refundId,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
