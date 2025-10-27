<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WoocommerceConnection extends Model
{
    use HasFactory;

    protected $fillable = [
        'team_id',
        'consumer_key',
        'consumer_secret',
        'store_url',
    ];

    protected $casts = [
        // Ensure sensitive credentials are encrypted in the database
        'consumer_key' => 'encrypted',
        'consumer_secret' => 'encrypted',
    ];

    /**
     * Get the team this connection belongs to.
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}