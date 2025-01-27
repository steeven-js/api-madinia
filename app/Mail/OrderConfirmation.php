<?php

namespace App\Mail;

use App\Models\EventOrder;
use Barryvdh\DomPDF\Facade\Pdf;
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

    public EventOrder $order;
    protected $qrCodePath;
    protected $invoicePath;

    public function __construct(EventOrder $order)
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

        // Générer la facture PDF
        $pdf = Pdf::loadView('pdf.invoice', ['order' => $this->order]);
        $this->invoicePath = storage_path('app/temp/invoice-' . $this->order->id . '.pdf');
        $pdf->save($this->invoicePath);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Confirmation de commande - Madin.IA',
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
                'order' => $this->order,
                'qrCodePath' => $this->qrCodePath,
            ],
        );
    }

    public function build()
    {
        return $this->attach($this->qrCodePath, [
                    'as' => 'qr-code.png',
                    'mime' => 'image/png',
                ])
                ->attach($this->invoicePath, [
                    'as' => 'facture-' . str_pad($this->order->id, 6, '0', STR_PAD_LEFT) . '.pdf',
                    'mime' => 'application/pdf',
                ]);
    }

    public function __destruct()
    {
        // Nettoyer les fichiers temporaires
        if (file_exists($this->qrCodePath)) {
            unlink($this->qrCodePath);
        }
        if (file_exists($this->invoicePath)) {
            unlink($this->invoicePath);
        }
    }
}
