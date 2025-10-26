<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SyncRun extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'status',
        'start_time',
        'end_time',
        'total_orders',
        'successful_invoices',
        'failed_invoices',
        'error_details',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'error_details' => 'array', // Will automatically serialize/unserialize JSON for error details
    ];

    /**
     * Get the user that owns the sync run.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}