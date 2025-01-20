<?php

namespace App\Models;

use Spatie\MediaLibrary\HasMedia;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\InteractsWithMedia;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class EventOrder extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $table = 'event_orders';

    protected $fillable = [
        'event_id',
        'status',
        'total_price',
        'session_id',
        'customer_email',
        'customer_name',
        'qr_code'
    ];

    protected $casts = [
        'total_price' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
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

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('qr-codes')
            ->useDisk('public');
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function history(): HasMany
    {
        return $this->hasMany(EventOrderHistory::class);
    }

    public function generateQrCode(): void
    {
        try {
            $qrData = [
                'order_id' => $this->id,
                'event_id' => $this->event_id,
                'timestamp' => time(),
                'hash' => hash('sha256', $this->id . $this->event_id . env('APP_KEY'))
            ];

            // Encoder les données en base64
            $encodedData = base64_encode(json_encode($qrData));

            // Générer un nom de fichier sécurisé
            $secureFileName = Str::random(40) . '_' . hash('sha256', $this->id . time() . Str::random(16)) . '.png';

            // Générer le QR code en PNG
            $qrCode = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('png')
                ->size(300)
                ->errorCorrection('H')
                ->margin(1)
                ->generate($encodedData);

            // Sauvegarder le QR code dans le storage via Media Library
            $this->clearMediaCollection('qr-codes');
            $this->addMediaFromString($qrCode)
                ->usingFileName($secureFileName)
                ->toMediaCollection('qr-codes');

            // Sauvegarder les données du QR code dans la base de données
            $this->qr_code = $encodedData;
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

    protected static function boot()
    {
        parent::boot();

        // Enregistrer l'historique lors de la création
        static::created(function ($order) {
            $order->history()->create([
                'new_status' => $order->status,
                'changed_by' => 'system',
                'metadata' => ['action' => 'order_created']
            ]);
        });

        // Enregistrer l'historique lors du changement de statut
        static::updated(function ($order) {
            if ($order->isDirty('status')) {
                $order->history()->create([
                    'old_status' => $order->getOriginal('status'),
                    'new_status' => $order->status,
                    'changed_by' => Auth::user() ? Auth::user()->name : 'system',
                    'metadata' => ['action' => 'status_changed']
                ]);
            }
        });
    }
}
