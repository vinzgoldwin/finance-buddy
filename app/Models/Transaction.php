<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Category;

class Transaction extends Model
{
    protected $fillable = [
        'user_id',
        'occurred_at',
        'description',
        'amount',
        'currency',
        'category_id',
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

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
