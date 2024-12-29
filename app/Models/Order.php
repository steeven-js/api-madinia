<?php

namespace App\Models;

use Spatie\MediaLibrary\HasMedia;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\InteractsWithMedia;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class Order extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

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

    protected $appends = ['qr_code_url'];

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

    public function generateQrCode(): void
    {
        try {
            $qrData = json_encode([
                'order_id' => $this->id,
                'event_id' => $this->event_id,
                'timestamp' => time(),
                'hash' => hash('sha256', $this->id . $this->event_id . env('APP_KEY'))
            ]);

            $this->qr_code = base64_encode($qrData);

            // Générer un nom de fichier sécurisé
            $secureFileName = Str::random(40) . '_' . hash('sha256', $this->id . time() . Str::random(16)) . '.png';

            // Générer le QR code en PNG
            $qrCode = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('png')
                ->size(300)
                ->errorCorrection('H')
                ->generate($this->qr_code);

            // Sauvegarder le QR code dans le storage via Media Library
            $this->clearMediaCollection('qr-codes');
            $this->addMediaFromString($qrCode)
                ->usingFileName($secureFileName)
                ->toMediaCollection('qr-codes');

            $this->save();
        } catch (\Exception $e) {
            Log::error('Erreur lors de la génération du QR code', [
                'order_id' => $this->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function getQrCodeUrlAttribute(): ?string
    {
        return $this->getFirstMediaUrl('qr-codes');
    }
}
