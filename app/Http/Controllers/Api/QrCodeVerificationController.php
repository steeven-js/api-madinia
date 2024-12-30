<?php

namespace App\Http\Controllers\Api;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;

class QrCodeVerificationController extends Controller
{
    /**
     * Vérifie la validité d'un QR code
     */
    public function verify(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'qr_code' => 'required|string'
            ]);

            // Décoder les données JSON
            $qrData = json_decode($request->qr_code, true);
            if (!$qrData) {
                return response()->json([
                    'valid' => false,
                    'message' => 'Format de QR code invalide'
                ], 400);
            }

            // Vérifier les données nécessaires
            if (!isset($qrData['order_id']) || !isset($qrData['event_id']) || !isset($qrData['hash'])) {
                return response()->json([
                    'valid' => false,
                    'message' => 'Données du QR code incomplètes'
                ], 400);
            }

            // Récupérer la commande
            $order = Order::with('event')->find($qrData['order_id']);
            if (!$order) {
                return response()->json([
                    'valid' => false,
                    'message' => 'Commande introuvable'
                ], 404);
            }

            // Vérifier que l'événement correspond
            if ($order->event_id != $qrData['event_id']) {
                return response()->json([
                    'valid' => false,
                    'message' => 'QR code invalide pour cet événement'
                ], 400);
            }

            // Vérifier le hash
            $expectedHash = hash('sha256', $order->id . $order->event_id . env('APP_KEY'));
            if ($qrData['hash'] !== $expectedHash) {
                return response()->json([
                    'valid' => false,
                    'message' => 'QR code non authentique'
                ], 400);
            }

            // Vérifier le statut de la commande
            if ($order->status !== Order::STATUS_PAID) {
                return response()->json([
                    'valid' => false,
                    'message' => 'Commande non payée',
                    'status' => $order->status
                ], 400);
            }

            // Vérifier la date de l'événement avec une marge de 2 jours
            $eventDate = $order->event->scheduled_date;
            $expirationDate = $eventDate->copy()->addDays(2);
            if (now()->isAfter($expirationDate)) {
                return response()->json([
                    'valid' => false,
                    'message' => 'Événement expiré',
                    'event_date' => $eventDate->format('Y-m-d H:i:s'),
                    'expiration_date' => $expirationDate->format('Y-m-d H:i:s')
                ], 400);
            }

            // QR code valide
            return response()->json([
                'valid' => true,
                'message' => 'QR code valide',
                'order' => [
                    'id' => $order->id,
                    'order_number' => str_pad($order->id, 6, '0', STR_PAD_LEFT),
                    'customer_name' => $order->customer_name,
                    'customer_email' => $order->customer_email,
                    'status' => $order->status,
                    'created_at' => $order->created_at->format('Y-m-d H:i:s')
                ],
                'event' => [
                    'id' => $order->event->id,
                    'title' => $order->event->title,
                    'scheduled_date' => $order->event->scheduled_date->format('Y-m-d H:i:s')
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur lors de la vérification du QR code', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'valid' => false,
                'message' => 'Erreur lors de la vérification du QR code'
            ], 500);
        }
    }
}
