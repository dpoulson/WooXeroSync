<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class XeroConnection extends Model
{
    use HasFactory;

    protected $fillable = [
        'team_id',
        'access_token',
        'refresh_token',
        'expires_at',
        'tenant_id',
        'tenant_name'
    ];

    protected $casts = [
        // Encrypt sensitive data automatically
        'access_token' => 'encrypted',
        'refresh_token' => 'encrypted',
        'tenant_id' => 'encrypted',
        'tenant_name' => 'string',
        // Standard datetime cast
        'expires_at' => 'datetime',
    ];

    /**
     * Get the team this connection belongs to.
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}