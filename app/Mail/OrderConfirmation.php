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
    protected $qrCodePath;

    public function __construct(Order $order)
    {
        $this->order = $order;

        // Générer le QR code en PNG
        $qrCode = QrCode::format('png')
            ->size(300)
            ->errorCorrection('H')
            ->generate($this->order->qr_code);

        // Sauvegarder temporairement le QR code
        $this->qrCodePath = storage_path('app/temp/qr-' . $this->order->id . '.png');
        file_put_contents($this->qrCodePath, $qrCode);
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
            with: [
                'qrCodePath' => $this->qrCodePath,
            ],
        );
    }

    public function build()
    {
        return $this->view('emails.orders.confirmation')
                    ->attach($this->qrCodePath, [
                        'as' => 'qr-code.png',
                        'mime' => 'image/png',
                    ]);
    }

    public function __destruct()
    {
        // Nettoyer le fichier temporaire
        if (file_exists($this->qrCodePath)) {
            unlink($this->qrCodePath);
        }
    }
}
