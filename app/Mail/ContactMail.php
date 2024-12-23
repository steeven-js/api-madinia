<?php

namespace App\Mail;

use App\Models\ContactMail as ContactMailModel;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;

class ContactMail extends Mailable
{
    use Queueable, SerializesModels;

    public $contactMail;

    /**
     * Create a new message instance.
     */
    public function __construct(ContactMailModel $contactMail)
    {
        $this->contactMail = $contactMail;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Nouveau message de contact',
            from: new \Illuminate\Mail\Mailables\Address('contact@madinia.fr', 'Madinina Web')
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.contact.send',
            with: [
                'contactMail' => $this->contactMail,
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

    /**
     * Get the message headers.
     */
    public function headers(): Headers
    {
        return new Headers(
            text: [
                'X-MT-Category' => 'Demande de contact',
            ],
        );
    }
}
