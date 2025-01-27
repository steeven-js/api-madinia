<?php

namespace App\Http\Controllers;

use Stripe\Stripe;
use App\Models\Event;
use App\Models\EventOrder;
use Illuminate\Http\Request;
use Stripe\Checkout\Session;
use App\Mail\OrderConfirmation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class EventCheckOutController extends Controller
{
    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    /**
     * Affiche les évènements
     */
    public function index()
    {
        $events = Event::All();

        // Json response for API
        return response()->json($events);
    }

    /**
     * Affiche un évènement
     */
    public function show($firebaseId)
    {
        $event = Event::where('firebaseId', $firebaseId)->firstOrFail();

        // Json response for API
        return response()->json($event);
    }

    // Modified checkout method to handle both GET and POST
    public function checkout(Request $request)
    {
        try {
            // Validate request data
            $request->validate([
                'eventId' => 'required|string',
                'title' => 'required|string',
                'price' => 'required|numeric',
                'imageUrl' => 'nullable|string',
                'returnUrl' => 'required|string'
            ]);

            // Get event from database
            $event = Event::where('firebaseId', $request->eventId)->firstOrFail();

            if (!$event->price) {
                throw new \Exception('Event price not set');
            }

            $session = Session::create([
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => 'eur',
                        'product_data' => [
                            'name' => $request->title,
                            'images' => $request->imageUrl ? [$request->imageUrl] : [],
                        ],
                        'unit_amount' => (int)($event->price * 100),
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'payment',
                'success_url' => $request->returnUrl . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => env('FRONTEND_URL') . '/cancel',
            ]);

            // Create order record
            EventOrder::create([
                'status' => 'unpaid',
                'total_price' => $event->price,
                'session_id' => $session->id,
                'event_id' => $event->id,
            ]);

            // Return session data
            return response()->json([
                'url' => $session->url,
                'sessionId' => $session->id
            ]);
        } catch (\Exception $e) {
            Log::error('Checkout error: ' . $e->getMessage());

            // Return proper error response
            return response()->json([
                'error' => $e->getMessage()
            ], 400);
        }
    }

    public function success(Request $request)
    {
        try {
            $sessionId = $request->get('session_id');
            if (!$sessionId) {
                throw new NotFoundHttpException('No session ID provided');
            }

            $session = Session::retrieve($sessionId);
            if (!$session) {
                throw new NotFoundHttpException('Session not found');
            }

            $order = EventOrder::where('session_id', $session->id)->first();
            if (!$order) {
                throw new NotFoundHttpException('Order not found');
            }

            if ($order->status === 'unpaid') {
                $order->status = 'paid';
                $order->save();
            }

            return view('success', [
                'order' => $order,
                'event' => $order->event
            ]);
        } catch (\Exception $e) {
            Log::error('Success page error: ' . $e->getMessage());
            return redirect('/')->with('error', 'Unable to process your order.');
        }
    }

    public function cancel()
    {
        return redirect(env('FRONTEND_URL') . '/cancel');
    }

    public function webhook()
    {
        $endpoint_secret = config('services.stripe.webhook_secret');

        $payload = @file_get_contents('php://input');
        $sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'];
        $event = null;

        try {
            $event = \Stripe\Webhook::constructEvent(
                $payload,
                $sig_header,
                $endpoint_secret
            );
        } catch (\Exception $e) {
            Log::error('Webhook error: ' . $e->getMessage());
            return response('', 400);
        }

        // Handle the event
        switch ($event->type) {
            case 'checkout.session.completed':
                try {
                    $session = $event->data->object;
                    Log::info('Processing checkout session', [
                        'session_id' => $session->id,
                        'customer_details' => $session->customer_details
                    ]);

                    $order = EventOrder::where('session_id', $session->id)->first();

                    if (!$order) {
                        Log::error('Order not found', ['session_id' => $session->id]);
                        return response()->json(['error' => 'Order not found'], 404);
                    }

                    Log::info('Found order', [
                        'order_id' => $order->id,
                        'status' => $order->status
                    ]);

                    $customerEmail = $session->customer_details->email ?? null;
                    $customerName = $session->customer_details->name ?? null;

                    DB::beginTransaction();
                    try {
                        if ($order->status === 'unpaid') {
                            // Mise à jour de la commande
                            $order->update([
                                'status' => 'paid',
                                'customer_email' => $customerEmail,
                                'customer_name' => $customerName
                            ]);

                            // Enregistrement de l'événement Stripe dans l'historique
                            $order->history()->create([
                                'old_status' => 'unpaid',
                                'new_status' => 'paid',
                                'stripe_event_id' => $event->id,
                                'stripe_event_type' => $event->type,
                                'changed_by' => 'stripe',
                                'metadata' => [
                                    'session_id' => $session->id,
                                    'customer_email' => $customerEmail,
                                    'customer_name' => $customerName
                                ]
                            ]);

                            // Génération du QR code
                            $order->generateQrCode();
                        }

                        DB::commit();

                        // Envoi de l'email de confirmation (déplacé en dehors de la condition unpaid)
                        if ($customerEmail) {
                            Mail::to($customerEmail)->send(new OrderConfirmation($order));
                            Log::info('Confirmation email sent', [
                                'order_id' => $order->id,
                                'email' => $customerEmail
                            ]);
                        }

                        Log::info('Order processed successfully', [
                            'order_id' => $order->id,
                            'stripe_event_id' => $event->id
                        ]);

                    } catch (\Exception $e) {
                        DB::rollBack();
                        Log::error('Error processing order', [
                            'order_id' => $order->id,
                            'error' => $e->getMessage()
                        ]);
                        throw $e;
                    }
                } catch (\Exception $e) {
                    Log::error('Error processing webhook', [
                        'error' => $e->getMessage()
                    ]);
                    return response()->json(['error' => $e->getMessage()], 500);
                }
                break;

            default:
                Log::info('Unhandled event type: ' . $event->type);
        }

        return response()->json(['status' => 'success']);
    }
}
