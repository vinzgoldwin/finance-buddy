<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransactionSubscription extends Model
{
    protected $fillable = [
        'user_id',
        'merchant_key',
        'hit_count',
        'avg_amount',
        'last_seen_at',
    ];

    protected $casts = [
        'last_seen_at' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
