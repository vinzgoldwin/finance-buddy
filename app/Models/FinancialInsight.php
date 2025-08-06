<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class FinancialInsight extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'period_start',
        'period_end',
        'version',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
    ];

    public function spendingInsights(): HasMany
    {
        return $this->hasMany(SpendingInsight::class);
    }

    public function savingsRecommendations(): HasMany
    {
        return $this->hasMany(SavingsRecommendation::class);
    }

    public function budgetingAssistances(): HasMany
    {
        return $this->hasMany(BudgetingAssistance::class);
    }

    public function financialHealths(): HasMany
    {
        return $this->hasMany(FinancialHealth::class);
    }

    public function periodSummary(): HasOne
    {
        return $this->hasOne(PeriodSummary::class);
    }
}
