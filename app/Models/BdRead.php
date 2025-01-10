<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BdRead extends Model
{
    use HasFactory;

    protected $table = 'bd_reads';

    protected $fillable = [
        'badge_id',
        'raw_data',
        'status',
        'message',
        'read_at',
        'read_location'
    ];

    protected $dates = [
        'read_at',
        'created_at',
        'updated_at'
    ];

    const STATUS_SUCCESS = 'success';
    const STATUS_ERROR = 'error';

    public function badge(): BelongsTo
    {
        return $this->belongsTo(Bd::class, 'badge_id');
    }
}
