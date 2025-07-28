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
        $incomeCategoryId = (int) config('finance.income_category_id', 12);

        $currency  = strtoupper($request->query('currency', 'USD'));

        if (! in_array($currency, ['USD', 'IDR'])) {
            $currency = 'USD';
        }

        $monthStr  = $request->query('date', now()->format('Y-m'));
        [$y, $m]   = array_pad(explode('-', $monthStr), 2, null);
        $startDate = Carbon::createSafe((int) $y,(int) $m, 1)->startOfMonth();
        $endDate   = $startDate->copy()->endOfMonth();

        // ── 2. One query for all totals, grouped by native currency ────────
        $rows = Transaction::whereBetween('date', [$startDate, $endDate])
            ->selectRaw(
            'currency,
             SUM(CASE WHEN category_id = ?  THEN amount ELSE 0 END) AS income,
             SUM(CASE WHEN category_id != ? THEN amount ELSE 0 END) AS expense',
            [$incomeCategoryId, $incomeCategoryId]
        )
            ->groupBy('currency')
            ->get()
            ->keyBy('currency');

        $usdIncome  = (float) optional($rows->get('USD'))->income;
        $usdExpense = (float) optional($rows->get('USD'))->expense;
        $idrIncome  = (float) optional($rows->get('IDR'))->income;
        $idrExpense = (float) optional($rows->get('IDR'))->expense;

        // ── 3. Convert only the side that needs conversion ─────────────────
        if ($currency === 'IDR') {
            $convUsdIncome  = $usdIncome  ? CurrencyConverter::convert($usdIncome)->from('USD')->to('IDR')->get() : 0;
            $convUsdExpense = $usdExpense ? CurrencyConverter::convert($usdExpense)->from('USD')->to('IDR')->get() : 0;
            $income  = $idrIncome  + $convUsdIncome;
            $expense = $idrExpense + $convUsdExpense;
        } else {
            $convIdrIncome  = $idrIncome  ? CurrencyConverter::convert($idrIncome)->from('IDR')->to('USD')->get() : 0;
            $convIdrExpense = $idrExpense ? CurrencyConverter::convert($idrExpense)->from('IDR')->to('USD')->get(): 0;
            $income  = $usdIncome  + $convIdrIncome;
            $expense = $usdExpense + $convIdrExpense;
        }

        $netBalance = $income - $expense;
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
            ->where('category_id', '!=', $incomeCategoryId)
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
            'metrics'      => [
                'income'   => $income,
                'expenses' => $expense,
                'netPct'   => $netPct,
            ],
            'monthly'     => $monthly,
            'categories'  => $categories,
            'recent'      => $recent,
        ]);
    }
}
