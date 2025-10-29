<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SyncRunLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'sync_run_id',
        'level',
        'message',
        'context',
    ];

    protected $casts = [
        'context' => 'array',
    ];

    public function syncRun()
    {
        return $this->belongsTo(SyncRun::class);
    }
}