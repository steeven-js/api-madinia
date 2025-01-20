@component('mail::message')
# Confirmation de remboursement

Bonjour {{ $order->customer_name }},

Nous vous confirmons que le remboursement de votre commande a été effectué avec succès.

**Détails de la commande :**
- Numéro de commande : {{ str_pad($order->id, 6, '0', STR_PAD_LEFT) }}
- Événement : {{ $order->event->title }}
- Montant remboursé : {{ number_format($order->total_price, 2, ',', ' ') }}€
- Date du remboursement : {{ now()->format('d/m/Y H:i') }}
- ID du remboursement : {{ $refundId }}

Le remboursement sera visible sur votre compte bancaire dans les 5 à 10 jours ouvrés, selon votre banque.

Si vous avez des questions concernant ce remboursement, n'hésitez pas à nous contacter.

Cordialement,<br>
L'équipe {{ config('app.name') }}
@endcomponent
