<?php

namespace App\Services;

use App\Models\SpendingSpike;
use App\Models\Transaction;
use App\Models\TransactionSubscription;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class TransactionAnalyticsService
{

    public function handle(Transaction $tx): void
    {
        $incomeCategoryId = (int) config('finance.income_category_id', 1);

        if (!$tx->exists) {
            return;
        }

        if ($tx->category_id === $incomeCategoryId) {
            if ($tx->merchant_key) {
                TransactionSubscription::where([
                    'user_id'      => $tx->user_id,
                    'merchant_key' => $tx->merchant_key,
                ])->delete();
            }
            return;
        }


        $this->updateSubscription($tx);
        $this->updateSpendingSpike($tx);
    }

    private function updateSubscription(Transaction $tx): void
    {
        if (!$tx->merchant_key) {
            return;
        }

        $since = Carbon::now()->subDays(90);

        $stats = Transaction::query()
            ->where('user_id', $tx->user_id)
            ->where('merchant_key', $tx->merchant_key)
            ->where('date', '>=', $since)
            ->selectRaw('COUNT(*)  as hit_count,
                         AVG(amount) as avg_amount,
                         MAX(date)   as last_seen_at')
            ->first();

        if ($stats->hit_count < 3) {
            TransactionSubscription::where([
                'user_id'      => $tx->user_id,
                'merchant_key' => $tx->merchant_key,
            ])->delete();
            return;
        }

        TransactionSubscription::updateOrCreate(
            [
                'user_id'      => $tx->user_id,
                'merchant_key' => $tx->merchant_key,
            ],
            [
                'hit_count'     => $stats->hit_count,
                'avg_amount'    => round($stats->avg_amount, 2),
                'last_seen_at'  => $stats->last_seen_at,
            ]
        );
    }

    private function updateSpendingSpike(Transaction $tx): void
    {
        if ($tx->amount <= 0) {
            return;
        }

        $since = Carbon::now()->subDays(60);

        $baseline = Transaction::query()
            ->where('user_id', $tx->user_id)
            ->where('category_id', $tx->category_id)
            ->whereNot('category_id', 1)
            ->where('amount', '>', 0)
            ->where('date', '>=', $since)
            ->selectRaw('AVG(amount) as mean, STDDEV_SAMP(amount) as sd')
            ->first();

        if (!$baseline->mean || !$baseline->sd) {
            return;
        }

        $threshold = $baseline->mean + 2 * $baseline->sd;

        if ($tx->amount <= $threshold) {
            SpendingSpike::where('transaction_id', $tx->id)->delete();
            return;
        }

        SpendingSpike::updateOrCreate(
            ['transaction_id' => $tx->id],
            [
                'user_id'       => $tx->user_id,
                'amount'        => $tx->amount,
                'baseline_mean' => round($baseline->mean, 2),
                'baseline_sd'   => round($baseline->sd, 2),
            ]
        );
    }
}
