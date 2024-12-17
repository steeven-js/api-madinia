<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Address;

class OrderConfirmation extends Mailable
{
    use Queueable, SerializesModels;

    public $order;

    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Confirmation de réservation #' . str_pad($this->order->id, 6, '0', STR_PAD_LEFT),
            from: new Address('noreply@madinia.fr', 'Madinia Events'),
            replyTo: [
                new Address('contact@madinia.fr', 'Support Madinia'),
            ],
            tags: ['order-confirmation'],
            metadata: [
                'order_id' => $this->order->id,
                'event_id' => $this->order->event_id,
            ],
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.orders.confirmation',
        );
    }
}
