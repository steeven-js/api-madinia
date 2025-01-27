@component('mail::message')
# Confirmation de commande

Merci pour votre commande !

**Détails de la commande :**
- Numéro de commande : {{ $order->id }}
- Événement : {{ $order->event->title }}
- Prix : {{ number_format($order->total_price, 2) }} €

Vous trouverez ci-joint votre QR code d'accès ainsi que votre facture.

@component('mail::button', ['url' => env('FRONTEND_URL')])
Voir mes billets
@endcomponent

Merci,<br>
{{ config('app.name') }}
@endcomponent
