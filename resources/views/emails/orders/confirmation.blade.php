@component('mail::message')
# Confirmation de votre réservation

Merci pour votre réservation pour l'événement "{{ $order->event->title }}"

**Détails de la commande :**
- Numéro de commande : {{ str_pad($order->id, 6, '0', STR_PAD_LEFT) }}
- Date de l'événement : {{ $order->event->scheduled_date->format('d/m/Y H:i') }}
- Montant : {{ number_format($order->total_price, 2) }}€

**Votre QR Code d'accès :**
<img src="{{ $message->embedData(file_get_contents($qrCodePath), 'qr-code.png') }}" alt="QR Code" style="width: 200px;">

Veuillez présenter ce QR code à l'entrée de l'événement.

Merci,<br>
{{ config('app.name') }}
@endcomponent
