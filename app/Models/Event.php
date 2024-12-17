<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use HasFactory;

    protected $table = 'events';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'firebaseId',
        'title',
        'price',
        'image_url',
        'scheduled_date',
        'status',
        'is_active',
        'stripe_event_id',
        'stripe_price_id',
        'activated_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'price' => 'decimal:2',
        'scheduled_date' => 'datetime',
        'activated_at' => 'datetime',
        'is_active' => 'boolean'
    ];
}
