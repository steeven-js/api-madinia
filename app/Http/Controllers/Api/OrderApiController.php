<?php

namespace App\Http\Controllers\Api;

use App\Models\EventOrder;
use Stripe\Stripe;
use Stripe\Refund;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Http\Requests\UpdateOrderRequest;
use App\Mail\RefundConfirmation;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class OrderApiController extends Controller
{
    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    /**
     * Display a listing of the orders.
     */
    public function index(): AnonymousResourceCollection
    {
        $orders = EventOrder::with('event')->latest()->get();
        return OrderResource::collection($orders);
    }

    /**
     * Display the specified order.
     */
    public function show(EventOrder $order): OrderResource
    {
        return new OrderResource($order->load('event'));
    }

    /**
     * Update the specified order in storage.
     */
    public function update(UpdateOrderRequest $request, EventOrder $order): OrderResource
    {
        try {
            DB::beginTransaction();

            $oldStatus = $order->status;
            $newStatus = $request->validated()['status'];

            // Si le statut change pour "refunded", effectuer le remboursement Stripe
            if ($oldStatus !== $newStatus && $newStatus === EventOrder::STATUS_REFUNDED) {
                try {
                    // Récupérer le paiement Stripe associé à la commande
                    $session = \Stripe\Checkout\Session::retrieve($order->session_id);
                    $paymentIntent = $session->payment_intent;

                    // Effectuer le remboursement
                    $refund = Refund::create([
                        'payment_intent' => $paymentIntent,
                    ]);

                    Log::info('Remboursement Stripe effectué', [
                        'order_id' => $order->id,
                        'refund_id' => $refund->id
                    ]);

                    // Envoyer l'email de confirmation de remboursement
                    if ($order->customer_email) {
                        try {
                            Mail::to($order->customer_email)
                                ->send(new RefundConfirmation($order, $refund->id));

                            Log::info('Email de confirmation de remboursement envoyé', [
                                'order_id' => $order->id,
                                'customer_email' => $order->customer_email
                            ]);
                        } catch (\Exception $e) {
                            Log::error('Erreur lors de l\'envoi de l\'email de confirmation de remboursement', [
                                'order_id' => $order->id,
                                'error' => $e->getMessage()
                            ]);
                            // Ne pas bloquer le processus si l'envoi de l'email échoue
                        }
                    } else {
                        Log::warning('Pas d\'email client pour envoyer la confirmation de remboursement', [
                            'order_id' => $order->id
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::error('Erreur lors du remboursement Stripe', [
                        'order_id' => $order->id,
                        'error' => $e->getMessage()
                    ]);
                    throw $e;
                }
            }

            // Mettre à jour le statut de la commande
            $order->update($request->validated());

            // Si le statut change pour "paid", générer le QR code
            if ($oldStatus !== $newStatus && $newStatus === EventOrder::STATUS_PAID && !$order->qr_code) {
                $order->generateQrCode();
            }

            DB::commit();

            return new OrderResource($order->fresh()->load('event'));
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur lors de la mise à jour de la commande', [
                'order_id' => $order->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Remove the specified order from storage.
     */
    public function destroy(EventOrder $order): JsonResponse
    {
        $order->delete();
        return response()->json(null, 204);
    }

    /**
     * Regenerate QR code for the order.
     */
    public function regenerateQrCode(EventOrder $order): OrderResource
    {
        $order->generateQrCode();
        return new OrderResource($order->fresh()->load('event'));
    }
}
