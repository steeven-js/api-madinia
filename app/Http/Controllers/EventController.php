<?php

namespace App\Http\Controllers;

use Stripe\Stripe;
use App\Models\Event;
use App\Models\Order;
use Illuminate\Http\Request;
use Stripe\Checkout\Session;
use App\Mail\OrderConfirmation;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class EventController extends Controller
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
                'cancel_url' => $request->returnUrl,
            ]);

            // Create order record
            Order::create([
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

            $order = Order::where('session_id', $session->id)->first();
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
            return redirect('/')->with('error', 'Unable to process your order.');  // Changé de home à /
        }
    }

    public function cancel()
    {
        return redirect('/')->with('error', 'Payment was cancelled.');  // Changé de home à /
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
                $session = $event->data->object;

                $order = Order::where('session_id', $session->id)->first();
                if (!$order) {
                    Log::error('Commande non trouvée pour session_id: ' . $session->id);
                    break;
                }

                if ($order && $order->status === 'unpaid') {
                    $order->status = 'paid';
                    $order->save();

                    try {
                        $customerEmail = $session->customer_details->email;

                        if ($customerEmail) {
                            Mail::to($customerEmail)->send(new OrderConfirmation($order));
                        } else {
                            Log::warning('No customer email found in Stripe session');
                        }
                    } catch (\Exception $e) {
                        Log::error('Failed to send order confirmation email: ' . $e->getMessage());
                        Log::error('Stack trace: ' . $e->getTraceAsString()); // Ajout de log
                    }
                }
                break;

            default:
                Log::info('Unhandled event type: ' . $event->type);
        }

        return response('');
    }
}
