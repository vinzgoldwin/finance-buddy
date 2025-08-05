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
        $excludedIds = [1, 7];
        if ($tx->amount <= 0 || in_array($tx->category_id, $excludedIds)) {
            return;
        }

        // ── 2. Configurable look-back window ────────────────────────────────
        $lookbackDays = (int) config('analytics.spike_lookback_days', 60);

        // ── 3. Baseline (exclude current tx) ────────────────────────────────
        $baseline = Transaction::query()
            ->where('user_id',     $tx->user_id)
            ->where('category_id', $tx->category_id)
            ->where('amount', '>', 0)
            ->whereDate('date', '>=', now()->subDays($lookbackDays))
            ->whereKeyNot($tx->getKey())
            ->selectRaw('AVG(amount) AS mean, STDDEV_SAMP(amount) AS sd,
                     COUNT(*)   AS n')
            ->first();

        if (!$baseline->n || $baseline->sd == 0) {      // nothing to compare
            SpendingSpike::where('transaction_id', $tx->id)->delete();
            return;
        }

        // ── 4. Z-score threshold (configurable) ─────────────────────────────
        $z = (float) config('analytics.spike_z', 2.0);
        $threshold = $baseline->mean + $z * $baseline->sd;

        // ── 5. Upsert or purge ──────────────────────────────────────────────
        if ($tx->amount > $threshold) {
            SpendingSpike::updateOrCreate(
                ['transaction_id' => $tx->id],
                [
                    'user_id'       => $tx->user_id,
                    'amount'        => $tx->amount,
                    'baseline_mean' => round($baseline->mean, 2),
                    'baseline_sd'   => round($baseline->sd,   2),
                ]
            );
        } else {
            SpendingSpike::where('transaction_id', $tx->id)->delete();
        }
    }
}
