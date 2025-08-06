<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PeriodSummary extends Model
{
    use HasFactory;

    protected $fillable = [
        'financial_insight_id',
        'period_start',
        'period_end',
        'total_income',
        'total_expense',
        'net_balance',
        'savings_rate_pct',
        'largest_tx_date',
        'largest_tx_description',
        'largest_tx_amount',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'largest_tx_date' => 'date',
    ];

    public function financialInsight(): BelongsTo
    {
        return $this->belongsTo(FinancialInsight::class);
    }

    public function topCategories(): HasMany
    {
        return $this->hasMany(PeriodSummaryTopCategory::class);
    }
}
