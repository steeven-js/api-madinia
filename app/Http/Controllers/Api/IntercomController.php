<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class IntercomController extends Controller
{
    public function generateHmac(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $email = $request->input('email');
        $secretKey = config('services.intercom.secret_key');

        if (!$secretKey) {
            return response()->json(['error' => 'Intercom secret key not configured'], 500);
        }

        $hmac = hash_hmac('sha256', $email, $secretKey);

        return response()->json(['hmac' => $hmac]);
    }
}
