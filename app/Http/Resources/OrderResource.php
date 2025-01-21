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
            'qr_code_url' => $this->qr_code_url,
            'created_at' => $this->created_at ? $this->created_at->format('Y-m-d H:i:s') : null,
            'updated_at' => $this->updated_at ? $this->updated_at->format('Y-m-d H:i:s') : null,
            'event' => $this->event ? [
                'id' => $this->event->id,
                'title' => $this->event->title,
                'scheduled_date' => $this->event->scheduled_date ? $this->event->scheduled_date->format('Y-m-d H:i:s') : null,
                'price' => $this->event->price,
                'status' => $this->event->status,
            ] : null,
            'history' => $this->whenLoaded('history', function () {
                return $this->history->map(function ($entry) {
                    return [
                        'id' => $entry->id,
                        'type' => $entry->stripe_event_type ?? ($entry->old_status ? 'status_changed' : 'order_created'),
                        'created' => $entry->created_at->format('Y-m-d H:i:s'),
                        'metadata' => [
                            'old_status' => $entry->old_status,
                            'new_status' => $entry->new_status,
                            'changed_by' => $entry->changed_by,
                            'description' => $this->getHistoryDescription($entry),
                        ],
                    ];
                });
            }),
        ];
    }

    private function getHistoryDescription($entry): string
    {
        if ($entry->stripe_event_type) {
            return "Événement Stripe : " . $entry->stripe_event_type;
        }

        if ($entry->old_status) {
            return "Statut modifié de {$entry->old_status} à {$entry->new_status}" .
                   ($entry->changed_by ? " par {$entry->changed_by}" : "");
        }

        return "Commande créée";
    }
}
