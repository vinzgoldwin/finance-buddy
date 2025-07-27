<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        // ------------------------------------------------------------------
        // 0.  Settings
        // ------------------------------------------------------------------
        $incomeCategoryId = (int) config('finance.income_category_id', 12);

        // ------------------------------------------------------------------
        // 1.  Metrics (all amounts stored as positive numbers)
        // ------------------------------------------------------------------
        $income   = Transaction::where('category_id', $incomeCategoryId)->sum('amount');
        $expenses = Transaction::where('category_id', '!=', $incomeCategoryId)->sum('amount');

        $netBalance = $income - $expenses;
        $netPct     = $income > 0 ? round(($netBalance / $income) * 100) : 0;

        $monthExpr  = "MONTH(`occurred_at`)";

        $monthly = Transaction::select(
            DB::raw("$monthExpr as month"),
            DB::raw("SUM(CASE WHEN category_id = {$incomeCategoryId} THEN amount ELSE 0 END)  as income"),
            DB::raw("SUM(CASE WHEN category_id != {$incomeCategoryId} THEN amount ELSE 0 END) as expenses")
        )
            ->whereYear('occurred_at', Carbon::now()->year)
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
            ->get(['id', 'date', 'description', 'amount', 'category_id'])
            ->map(fn ($t) => [
                'id'          => $t->id,
                'date'        => Carbon::parse($t->date)->format('M d'),
                'description' => $t->description,
                'category'    => $t->category?->name ?? '—',
                // Make expenses negative so UI can color them red
                'amount'      => $t->category_id == $incomeCategoryId
                    ?  (float)  $t->amount
                    : -(float) $t->amount,
            ]);

        // ------------------------------------------------------------------
        // 5.  Return to Inertia
        // ------------------------------------------------------------------
        return Inertia::render('Dashboard', [
            'metrics'     => [
                'income'   => $income,
                'expenses' => $expenses,
                'netPct'   => $netPct,
            ],
            'monthly'     => $monthly,
            'categories'  => $categories,
            'recent'      => $recent,
        ]);
    }
}
