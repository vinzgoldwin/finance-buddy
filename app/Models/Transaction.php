<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    protected $fillable = [
        'user_id',
        'occurred_at',
        'description',
        'amount',
        'currency',
        'category',
        'meta',
    ];

    protected $casts = [
        'occurred_at' => 'date',
        'meta'        => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
