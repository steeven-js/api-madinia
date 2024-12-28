<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Address;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class OrderConfirmation extends Mailable
{
    use Queueable, SerializesModels;

    public $order;
    public $qrCodeImage;

    public function __construct(Order $order)
    {
        $this->order = $order;

        // Générer le QR code si ce n'est pas déjà fait
        if (!$this->order->qr_code) {
            $this->order->qr_code = $this->order->generateQrCode();
            $this->order->save();
        }

        // Générer l'image du QR code
        $this->qrCodeImage = base64_encode(QrCode::format('png')
            ->size(300)
            ->generate($this->order->qr_code));
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
