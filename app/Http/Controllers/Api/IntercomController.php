<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class IntercomController extends Controller
{
    public function generateHmac(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $secretKey = config('services.intercom.secret_key');

        if (empty($secretKey)) {
            Log::error('Intercom secret key not configured');
            return response()->json(['error' => 'Integration configuration error'], 500);
        }

        try {
            $hmac = hash_hmac('sha256', $request->email, $secretKey);
            return response()->json(['hmac' => $hmac]);
        } catch (\Exception $e) {
            Log::error('HMAC generation failed: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to generate secure hash'], 500);
        }
    }
}
