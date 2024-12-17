<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
        'event_id'
    ];

    /**
     * Get the event associated with the order.
     */
    public function event()
    {
        return $this->belongsTo(Event::class);
    }
}
