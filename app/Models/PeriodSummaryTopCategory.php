<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PeriodSummaryTopCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'period_summary_id',
        'category',
        'amount',
        'rank',
    ];

    public function periodSummary(): BelongsTo
    {
        return $this->belongsTo(PeriodSummary::class);
    }
}
