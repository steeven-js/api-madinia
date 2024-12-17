@component('mail::message')
![Logo](https://firebasestorage.googleapis.com/v0/b/jsprod-admin.appspot.com/o/logo%2FLogo2.jpg?alt=media&token=c4d0ac89-637f-47c4-8038-836f1bfeaba9)

# Confirmation de votre réservation

Merci d'avoir réservé pour notre événement ! Voici les détails de votre commande.

@component('mail::panel')
## Détails de la commande
- **Numéro de commande :** #{{ str_pad($order->id, 6, '0', STR_PAD_LEFT) }}
- **Montant payé :** {{ number_format($order->total_price, 2) }} €
- **Statut :** {{ ucfirst($order->status) }}
- **Date d'achat :** {{ $order->created_at->format('d/m/Y à H:i') }}
@endcomponent

@if ($order->event)
@component('mail::panel')
## Détails de l'événement
- **Événement :** {{ $order->event->title }}
- **Date :** {{ \Carbon\Carbon::parse($order->event->scheduled_date)->format('d/m/Y à H:i') }}
- **Lieu :** Madinia Events
@endcomponent
@endif

@component('mail::table')
| Information importante |
|-------------------------|
| Veuillez vous présenter 15 minutes avant le début de l'événement. |
| N'oubliez pas de présenter ce mail ou votre numéro de commande à l'entrée. |
@endcomponent

@component('mail::button', ['url' => config('app.url'), 'color' => 'primary'])
Voir les détails de ma commande
@endcomponent

---

## Besoin d'aide ?
- **Email :** contact@madinia.fr
- **Téléphone :** +596 696 XX XX XX
- **Site web :** [www.madinia.fr](https://madinia.fr)

---

## Cette confirmation de commande tient lieu de billet d'entrée. Conservez-la précieusement et présentez-la le jour de l'événement.

Cordialement,
L'équipe {{ config('app.name') }}

<small>
Ce message est envoyé automatiquement, merci de ne pas y répondre directement.
Pour toute question, utilisez les coordonnées ci-dessus.
</small>
@endcomponent
