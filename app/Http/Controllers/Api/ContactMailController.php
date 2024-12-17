<?php

namespace App\Http\Controllers\Api;

use App\Models\ContactMail;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Mail;
use App\Mail\ContactMail as SendMail;

class ContactMailController extends Controller
{
    public function store(Request $request)
    {
        // Validation des données
        $validatedData = $request->validate([
            'services' => 'required|array',
            'services.*' => 'string',
            'budget' => 'required|array|size:2',
            'budget.*' => 'integer',
            'company' => 'required|string',
            'email' => 'required|email',
            'firstName' => 'required|string',
            'lastName' => 'required|string',
            'message' => 'required|string',
            'phoneNumber' => 'nullable|string',
            'website' => 'nullable|url',
        ]);

        // Création d'un nouveau contact de messagerie
        $contactMail = ContactMail::create([
            'services' => json_encode($validatedData['services']),
            'budget' => json_encode($validatedData['budget']),
            'company' => $validatedData['company'],
            'email' => $validatedData['email'],
            'firstName' => $validatedData['firstName'],
            'lastName' => $validatedData['lastName'],
            'message' => $validatedData['message'],
            'phoneNumber' => $validatedData['phoneNumber'] ?? null,
            'website' => $validatedData['website'] ?? null,
        ]);

        // Envoi de l'e-mail

        $recipients = [
            'd.brault@madin-ia.com',
            'jh.joseph@madin-ia.com',
            's.jacques@madin-ia.com',
            'a.loza@madin-ia.com'
        ];

        // $recipients = [
        //     's.jacques@madin-ia.com'
        // ];

        Mail::to($recipients)
        ->send(new SendMail($contactMail));

        // Retourne les informations du nouveau contact de messagerie au format JSON
        return response()->json($contactMail, 201);
    }
}
