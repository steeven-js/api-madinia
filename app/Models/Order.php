<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Order extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'status',
        'total_price',
        'session_id',
        'event_id',
        'qr_code',
        'customer_email',
        'customer_name'
    ];

    protected $casts = [
        'total_price' => 'decimal:2',
    ];

    const STATUS_UNPAID = 'unpaid';
    const STATUS_PAID = 'paid';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_REFUNDED = 'refunded';

    public static function getStatuses(): array
    {
        return [
            self::STATUS_UNPAID,
            self::STATUS_PAID,
            self::STATUS_CANCELLED,
            self::STATUS_REFUNDED,
        ];
    }

    /**
     * Get the event associated with the order.
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function generateQrCode(): string
    {
        // Générer une chaîne unique pour le QR code
        $qrData = json_encode([
            'order_id' => $this->id,
            'event_id' => $this->event_id,
            'timestamp' => time(),
            'hash' => hash('sha256', $this->id . $this->event_id . env('APP_KEY'))
        ]);

        return base64_encode($qrData);
    }
}
