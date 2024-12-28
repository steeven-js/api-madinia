<?php

namespace App\Http\Controllers\Api;

use App\Models\Order;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Http\Requests\UpdateOrderRequest;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class OrderApiController extends Controller
{
    /**
     * Display a listing of the orders.
     */
    public function index(): AnonymousResourceCollection
    {
        $orders = Order::with('event')->latest()->get();
        return OrderResource::collection($orders);
    }

    /**
     * Display the specified order.
     */
    public function show(Order $order): OrderResource
    {
        return new OrderResource($order->load('event'));
    }

    /**
     * Update the specified order in storage.
     */
    public function update(UpdateOrderRequest $request, Order $order): OrderResource
    {
        $order->update($request->validated());
        return new OrderResource($order->fresh()->load('event'));
    }

    /**
     * Remove the specified order from storage.
     */
    public function destroy(Order $order): JsonResponse
    {
        $order->delete();
        return response()->json(null, 204);
    }
}
