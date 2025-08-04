<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Mgcodeur\CurrencyConverter\Facades\CurrencyConverter;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $incomeCategoryId  = (int) config('finance.income_category_id', 1);
        $savingsCategoryId = (int) config('finance.savings_category_id', 7);

        $currency  = strtoupper($request->query('currency', 'IDR'));

        if (! in_array($currency, ['USD', 'IDR'])) {
            $currency = 'IDR';
        }

        $monthStr  = $request->query('date', now()->format('Y-m'));
        $base = now()->copy()->startOfMonth();

        $monthOptions = collect(range(0, 5))
            ->map(function ($i) use ($base) {
                $d = $base->copy()->subMonths($i);
                return [
                    'value' => $d->format('Y-m'),
                    'label' => $d->translatedFormat('F Y'),
                ];
            });

        [$y, $m]   = array_pad(explode('-', $monthStr), 2, null);
        $startDate = Carbon::createSafe((int) $y,(int) $m, 1)->startOfMonth();
        $endDate   = $startDate->copy()->endOfMonth();

        $periodStart = $startDate->copy()->subMonths(5)->startOfMonth();

        $barRaw = Transaction::whereBetween('date', [$periodStart, $endDate])
            ->selectRaw(
                'YEAR(date)  as yr,
                 MONTH(date) as mo,
                 currency,
                 SUM(CASE WHEN category_id = ? THEN amount ELSE 0 END)  as income,
                 SUM(CASE WHEN category_id = ? THEN amount ELSE 0 END)  as savings,
                 SUM(CASE WHEN category_id NOT IN (?, ?) THEN amount ELSE 0 END) as expenses',
                [$incomeCategoryId, $savingsCategoryId, $incomeCategoryId, $savingsCategoryId]
            )
            ->groupBy('yr', 'mo', 'currency')
            ->get();

        $barData = collect(range(0,5))
            ->map(function ($offset) use ($periodStart, $barRaw, $currency, $incomeCategoryId) {
                $month = $periodStart->copy()->addMonths($offset);
                $rows  = $barRaw->where('yr', $month->year)->where('mo', $month->month);

                $usdInc = (float) $rows->where('currency','USD')->sum('income');
                $usdExp = (float) $rows->where('currency','USD')->sum('expenses');
                $usdSav = (float) $rows->where('currency','USD')->sum('savings');
                $idrInc = (float) $rows->where('currency','IDR')->sum('income');
                $idrExp = (float) $rows->where('currency','IDR')->sum('expenses');
                $idrSav = (float) $rows->where('currency','IDR')->sum('savings');

                if ($currency === 'IDR') {
                    $usdInc = $usdInc ? CurrencyConverter::convert($usdInc)->from('USD')->to('IDR')->get() : 0;
                    $usdExp = $usdExp ? CurrencyConverter::convert($usdExp)->from('USD')->to('IDR')->get() : 0;
                    $usdSav = $usdSav ? CurrencyConverter::convert($usdSav)->from('USD')->to('IDR')->get() : 0;
                } else {
                    $idrInc = $idrInc ? CurrencyConverter::convert($idrInc)->from('IDR')->to('USD')->get() : 0;
                    $idrExp = $idrExp ? CurrencyConverter::convert($idrExp)->from('IDR')->to('USD')->get() : 0;
                    $idrSav = $idrSav ? CurrencyConverter::convert($idrSav)->from('IDR')->to('USD')->get() : 0;
                }

                return [
                    'name'     => $month->format('M'),
                    'income'   => round($usdInc + $idrInc, 2),
                    'expenses' => round($usdExp + $idrExp, 2),
                    'savings'  => round($usdSav + $idrSav, 2),
                ];
            });

        // ── 2. One query for all totals, grouped by native currency ────────
        $rows = Transaction::whereBetween('date', [$startDate, $endDate])
            ->selectRaw(
                'currency,
                 SUM(CASE WHEN category_id = ?  THEN amount ELSE 0 END) AS income,
                 SUM(CASE WHEN category_id = ?  THEN amount ELSE 0 END) AS savings,
                 SUM(CASE WHEN category_id NOT IN (?, ?) THEN amount ELSE 0 END) AS expense',
                [$incomeCategoryId, $savingsCategoryId, $incomeCategoryId, $savingsCategoryId]
            )
            ->groupBy('currency')
            ->get()
            ->keyBy('currency');

        $usdIncome  = (float) optional($rows->get('USD'))->income;
        $usdExpense = (float) optional($rows->get('USD'))->expense;
        $usdSavings = (float) optional($rows->get('USD'))->savings;
        $idrIncome  = (float) optional($rows->get('IDR'))->income;
        $idrExpense = (float) optional($rows->get('IDR'))->expense;
        $idrSavings = (float) optional($rows->get('IDR'))->savings;

        // ── 3. Convert only the side that needs conversion ─────────────────
        if ($currency === 'IDR') {
            $convUsdIncome  = $usdIncome  ? CurrencyConverter::convert($usdIncome)->from('USD')->to('IDR')->get() : 0;
            $convUsdSavings = $usdSavings ? CurrencyConverter::convert($usdSavings)->from('USD')->to('IDR')->get() : 0;
            $convUsdExpense = $usdExpense ? CurrencyConverter::convert($usdExpense)->from('USD')->to('IDR')->get() : 0;
            $income  = $idrIncome  + $convUsdIncome;
            $expense = $idrExpense + $convUsdExpense;
            $savings = $idrSavings + $convUsdSavings;
        } else {
            $convIdrIncome  = $idrIncome  ? CurrencyConverter::convert($idrIncome)->from('IDR')->to('USD')->get() : 0;
            $convIdrExpense = $idrExpense ? CurrencyConverter::convert($idrExpense)->from('IDR')->to('USD')->get(): 0;
            $convIdrSavings = $idrSavings ? CurrencyConverter::convert($idrSavings)->from('IDR')->to('USD')->get(): 0;
            $income  = $usdIncome  + $convIdrIncome;
            $expense = $usdExpense + $convIdrExpense;
            $savings = $usdSavings + $convIdrSavings;
        }

        $netBalance = $income - $expense - $savings;
        $netPct     = $income > 0 ? round(($netBalance / $income) * 100) : 0;

        $monthExpr  = "MONTH(`date`)";

        $monthly = Transaction::select(
            DB::raw("$monthExpr as month"),
            DB::raw("SUM(CASE WHEN category_id = {$incomeCategoryId} THEN amount ELSE 0 END)  as income"),
            DB::raw("SUM(CASE WHEN category_id != {$incomeCategoryId} THEN amount ELSE 0 END) as expenses")
        )
            ->whereYear('date', Carbon::now()->year)
            ->groupBy(DB::raw($monthExpr))
            ->orderBy(DB::raw($monthExpr))
            ->get()
            ->map(fn ($row) => [
                'month'    => Carbon::create()->month((int) $row->month)->format('M'),
                'income'   => (float) $row->income,
                'expenses' => (float) $row->expenses,
            ]);

        // ------------------------------------------------------------------
        // 3.  Spending categories donut (skip income category)
        // ------------------------------------------------------------------
        $categories = Transaction::with('category')
            ->whereBetween('date', [$startDate, $endDate])
            ->whereNotIn('category_id', [$incomeCategoryId, $savingsCategoryId])
            ->selectRaw('category_id, SUM(amount) as total')
            ->groupBy('category_id')
            ->get()
            ->map(fn ($row) => [
                'label' => $row->category?->name ?? 'Unknown',
                'value' => (float) $row->total,
            ]);

        // ------------------------------------------------------------------
        // 4.  Recent transactions table (5 latest)
        // ------------------------------------------------------------------
        $recent = Transaction::with('category')
            ->latest('date')
            ->take(5)
            ->get(['id', 'date', 'description', 'amount', 'category_id', 'currency'])
            ->map(fn ($t) => [
                'id'          => $t->id,
                'date'        => Carbon::parse($t->date)->format('M d'),
                'description' => $t->description,
                'category'    => $t->category?->name ?? '—',
                'amount'      => $t->category_id == $incomeCategoryId ?  (float)  $t->amount : -(float) $t->amount,
                'currency'    => $t->currency
            ]);

        // ------------------------------------------------------------------
        // 5.  Return to Inertia
        // ------------------------------------------------------------------
        return Inertia::render('Dashboard', [
            'currency'     => $currency,
            'date'         => $monthStr,
            'monthOptions' => $monthOptions,
            'metrics'      => [
                'income'   => $income,
                'expenses' => $expense,
                'netPct'   => $netPct,
                'savings'  => $savings,
            ],
            'monthly'     => $monthly,
            'categories'  => $categories,
            'recent'      => $recent,
            'barData'     => $barData,
        ]);
    }
}
