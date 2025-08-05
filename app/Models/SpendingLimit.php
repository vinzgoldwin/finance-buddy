<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SpendingLimit extends Model
{
    protected $fillable = [
        'user_id',
        'amount',
        'interval',
        'currency',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
