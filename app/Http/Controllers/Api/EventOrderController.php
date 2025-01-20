<?php

namespace App\Http\Controllers\Api;

use App\Models\EventOrder;
use App\Models\EventOrderHistory;
use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use Illuminate\Http\Request;

class EventOrderController extends Controller
{
    public function update(Request $request, $id)
    {
        $order = EventOrder::findOrFail($id);
        $oldStatus = $order->status;
        $newStatus = $request->input('status');

        // Mise à jour du statut
        $order->update(['status' => $newStatus]);

        // L'historique est automatiquement créé grâce au boot() dans le modèle

        return new OrderResource($order->load('event', 'history'));
    }

    public function show($id)
    {
        $order = EventOrder::with(['event', 'history'])->findOrFail($id);
        return new OrderResource($order);
    }

    public function index()
    {
        $orders = EventOrder::with(['event'])->orderBy('created_at', 'desc')->get();
        return OrderResource::collection($orders);
    }
}
