<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Bd extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'bd';

    protected $fillable = [
        'badge_id',
        'raw_data',
        'status',
        'owner_name',
        'owner_email',
        'owner_phone',
        'notes',
        'last_read_at',
        'read_count'
    ];

    protected $dates = [
        'last_read_at',
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';
    const STATUS_BLOCKED = 'blocked';

    public function reads(): HasMany
    {
        return $this->hasMany(BdRead::class, 'badge_id');
    }

    public function recordRead($rawData, $status = 'success', $message = null): void
    {
        // Créer l'enregistrement de lecture
        $this->reads()->create([
            'raw_data' => $rawData,
            'status' => $status,
            'message' => $message,
            'read_at' => now(),
        ]);

        // Mettre à jour les statistiques du badge
        $this->update([
            'last_read_at' => now(),
            'read_count' => $this->read_count + 1
        ]);
    }
}
