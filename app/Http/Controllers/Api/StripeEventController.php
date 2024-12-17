<?php

namespace App\Http\Controllers\Api;

use Stripe\Price;
use Stripe\Stripe;
use Stripe\Product;
use Stripe\StripeClient;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Stripe\Exception\ApiErrorException;

class StripeEventController extends Controller
{
    private StripeClient $stripeClient;

    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret'));
        $this->stripeClient = new StripeClient(config('services.stripe.secret'));
    }

    /**
     * Create a new Stripe product with price
     */
    public function createEvent(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'imageUrl' => 'nullable|string'
        ]);

        try {
            $event = Product::create([
                'name' => $validated['title'],
                'type' => 'service',
                'images' => $validated['imageUrl'] ? [$validated['imageUrl']] : []
            ]);

            $price = Price::create([
                'product' => $event->id,
                'unit_amount' => (int) ($validated['price'] * 100),
                'currency' => 'eur',
            ]);

            return response()->json([
                'id' => $event->id,
                'price_id' => $price->id,
                'name' => $event->name,
                'price' => $validated['price'],
                'imageUrl' => $validated['imageUrl'] ?? null,
            ], 201);
        } catch (ApiErrorException $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Update an existing Stripe product and its price
     */
    public function updateEvent(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'imageUrl' => 'nullable|string'
        ]);

        try {
            $event = $this->stripeClient->products->update($id, [
                'name' => $validated['title'],
                'images' => $validated['imageUrl'] ? [$validated['imageUrl']] : []
            ]);

            // Handle price update if needed
            $prices = $this->stripeClient->prices->search([
                'query' => "product:'$id' AND active:'true'",
                'limit' => 1
            ]);

            $priceId = null;
            if (!empty($prices->data)) {
                $currentPrice = $prices->data[0];
                $newAmount = (int) ($validated['price'] * 100);

                if ($currentPrice->unit_amount !== $newAmount) {
                    $this->stripeClient->prices->update($currentPrice->id, ['active' => false]);
                    $newPrice = Price::create([
                        'product' => $id,
                        'unit_amount' => $newAmount,
                        'currency' => 'eur'
                    ]);
                    $priceId = $newPrice->id;
                } else {
                    $priceId = $currentPrice->id;
                }
            }

            return response()->json([
                'id' => $event->id,
                'price_id' => $priceId,
                'name' => $event->name,
                'price' => $validated['price'],
                'imageUrl' => $validated['imageUrl'] ?? null,
            ]);
        } catch (ApiErrorException $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get a Stripe product by ID
     */
    public function getEvent(string $id): JsonResponse
    {
        try {
            $event = $this->stripeClient->products->retrieve($id);

            // Get the active price
            $prices = $this->stripeClient->prices->search([
                'query' => "product:'$id' AND active:'true'",
                'limit' => 1
            ]);

            $price = !empty($prices->data) ? $prices->data[0]->unit_amount / 100 : null;
            $priceId = !empty($prices->data) ? $prices->data[0]->id : null;

            return response()->json([
                'id' => $event->id,
                'price_id' => $priceId,
                'name' => $event->name,
                'price' => $price,
                'imageUrl' => !empty($event->images) ? $event->images[0] : null,
            ]);
        } catch (ApiErrorException $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Delete a Stripe product
     */
    public function archiveEvent(string $id): JsonResponse
    {
        // Archive the product
        try {
            $event = $this->stripeClient->products->update($id, ['active' => false]);
            return response()->json(['id' => $event->id]);
        } catch (ApiErrorException $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Update product image
     */
    public function updateImage(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'imageUrl' => 'required|string'
        ]);

        try {
            $event = $this->stripeClient->products->update($id, [
                'images' => [$validated['imageUrl']]
            ]);

            return response()->json([
                'id' => $event->id,
                'imageUrl' => !empty($event->images) ? $event->images[0] : null,
            ]);
        } catch (ApiErrorException $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
