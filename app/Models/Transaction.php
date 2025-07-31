<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Category;

class Transaction extends Model
{
    protected $fillable = [
        'user_id',
        'date',
        'description',
        'merchant_key',
        'amount',
        'currency',
        'category_id',
        'meta',
    ];

    protected $casts = [
        'date'        => 'date',
        'meta'        => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
