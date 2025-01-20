<?php

namespace App\Http\Controllers\Api;

use App\Models\EventOrder;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class PaymentVerificationController extends Controller
{
    public function verifyPayment(string $sessionId)
    {
        $order = EventOrder::where('session_id', $sessionId)->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Paiement non trouvé'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'order' => [
                'id' => $order->id,
                'total_price' => $order->total_price,
                'event_id' => $order->event_id,
                'created_at' => $order->created_at,
                'status' => $order->status,
                'event_title' => $order->event->title,
                'event_scheduled_date' => $order->event->scheduled_date,
                'event_image_url' => $order->event->image_url,
            ]
        ]);
    }
}
