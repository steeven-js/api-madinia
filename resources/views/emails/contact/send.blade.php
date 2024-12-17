@component('mail::message')
# Nouveau message de contact

Vous avez reçu un nouveau message de contact de **{{ $contactMail->firstName }} {{ $contactMail->lastName }}** de l'entreprise **{{ $contactMail->company }}**.

## Détails du contact :
- Email : {{ $contactMail->email }}
- Téléphone : {{ $contactMail->phoneNumber ?? 'Non fourni' }}
- Site web : {{ $contactMail->website ?? 'Non fourni' }}

## Services demandés :
@foreach(json_decode($contactMail->services) as $service)
- {{ $service }}
@endforeach

## Budget :
{{ implode(' - ', json_decode($contactMail->budget)) }} €

## Message :
{{ $contactMail->message }}

Merci,<br>
{{ config('app.name') }}
@endcomponent
