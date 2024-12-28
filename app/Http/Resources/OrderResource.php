<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_number' => str_pad($this->id, 6, '0', STR_PAD_LEFT),
            'status' => $this->status,
            'total_price' => $this->total_price,
            'session_id' => $this->session_id,
            'customer_email' => $this->customer_email,
            'customer_name' => $this->customer_name,
            'qr_code' => $this->qr_code,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
            'event' => [
                'id' => $this->event->id,
                'title' => $this->event->title,
                'scheduled_date' => $this->event->scheduled_date->format('Y-m-d H:i:s'),
                'price' => $this->event->price,
                'status' => $this->event->status,
            ],
        ];
    }
}
