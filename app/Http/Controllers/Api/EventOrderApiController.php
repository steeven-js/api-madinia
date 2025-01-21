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
use Illuminate\Support\Facades\Auth;

class EventOrderApiController extends Controller
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
        $orders = EventOrder::with(['event', 'history'])->latest()->get();
        return OrderResource::collection($orders);
    }

    /**
     * Display the specified order.
     */
    public function show($id): OrderResource|JsonResponse
    {
        try {
            $order = EventOrder::find($id);

            if (!$order) {
                return response()->json([
                    'message' => 'Commande non trouvée',
                    'error' => 'La commande avec l\'ID ' . $id . ' n\'existe pas'
                ], 404);
            }

            $order = $order->load(['event', 'history']);
            return new OrderResource($order);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération de la commande', [
                'order_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'message' => 'Erreur lors de la récupération de la commande',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified order in storage.
     */
    public function update(UpdateOrderRequest $request, $id): OrderResource|JsonResponse
    {
        try {
            DB::beginTransaction();

            $order = EventOrder::find($id);
            if (!$order) {
                return response()->json([
                    'message' => 'Commande non trouvée',
                    'error' => 'La commande avec l\'ID ' . $id . ' n\'existe pas'
                ], 404);
            }

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

                    // Enregistrer l'événement de remboursement dans l'historique
                    $order->history()->create([
                        'old_status' => $oldStatus,
                        'new_status' => $newStatus,
                        'stripe_event_id' => $refund->id,
                        'stripe_event_type' => 'refund.created',
                        'changed_by' => Auth::user() ? Auth::user()->name : 'system',
                        'metadata' => [
                            'payment_intent' => $paymentIntent,
                            'refund_amount' => $order->total_price
                        ]
                    ]);

                    // Envoyer l'email de confirmation de remboursement
                    if ($order->customer_email) {
                        try {
                            Mail::to($order->customer_email)
                                ->send(new RefundConfirmation($order, $refund->id));
                        } catch (\Exception $e) {
                            // Ne pas bloquer le processus si l'envoi de l'email échoue
                        }
                    }
                } catch (\Exception $e) {
                    DB::rollBack();
                    return response()->json([
                        'message' => 'Erreur lors du remboursement',
                        'error' => $e->getMessage()
                    ], 500);
                }
            }

            // Mettre à jour le statut de la commande
            $order->update($request->validated());

            // Si le statut change pour "paid", générer le QR code
            if ($oldStatus !== $newStatus && $newStatus === EventOrder::STATUS_PAID && !$order->qr_code) {
                $order->generateQrCode();
            }

            DB::commit();

            $order = $order->fresh()->load(['event', 'history']);
            return new OrderResource($order);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Erreur lors de la mise à jour de la commande',
                'error' => $e->getMessage()
            ], 500);
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

    /**
     * Generate and return invoice PDF for the order.
     */
    public function generateInvoice(EventOrder $order): JsonResponse
    {
        try {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.invoice', ['order' => $order->load('event')]);

            return response()->json([
                'invoice_url' => 'data:application/pdf;base64,' . base64_encode($pdf->output())
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Erreur lors de la génération de la facture'], 500);
        }
    }
}
