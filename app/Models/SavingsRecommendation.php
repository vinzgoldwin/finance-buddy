<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SavingsRecommendation extends Model
{
    use HasFactory;

    protected $fillable = [
        'financial_insight_id',
        'content',
    ];

    public function financialInsight(): BelongsTo
    {
        return $this->belongsTo(FinancialInsight::class);
    }
}
