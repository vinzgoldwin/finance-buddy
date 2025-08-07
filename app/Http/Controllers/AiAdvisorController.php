<?php

namespace App\Http\Controllers;

use App\Models\FinancialInsight;
use App\Models\Transaction;
use App\Services\TransactionAnalysisService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;

class AiAdvisorController extends Controller
{
    protected TransactionAnalysisService $analysisService;

    public function __construct(TransactionAnalysisService $analysisService)
    {
        $this->analysisService = $analysisService;
    }

    /**
     * Display the AI advisor page
     *
     * @return \Inertia\Response
     */
    public function index()
    {
        $userId = auth()->id();

        $latest = FinancialInsight::with([
            'spendingInsights' => function ($q) { $q->latest(); },
            'savingsRecommendations' => function ($q) { $q->latest(); },
            'budgetingAssistances' => function ($q) { $q->latest(); },
            'financialHealths' => function ($q) { $q->latest(); },
            'periodSummary.topCategories' => function ($q) { $q->orderBy('rank'); },
        ])
            ->where('user_id', $userId)
            ->orderByDesc('period_end')
            ->orderByDesc('version')
            ->first();

        $insights = null;
        if ($latest) {
            $insights = [
                'spending_insights' => optional($latest->spendingInsights->first())->content ?? '',
                'savings_recommendations' => optional($latest->savingsRecommendations->first())->content ?? '',
                'budgeting_assistance' => optional($latest->budgetingAssistances->first())->content ?? '',
                'financial_health' => optional($latest->financialHealths->first())->content ?? '',
            ];
        }

        return Inertia::render('AIAdvisor', [
            'initialInsights' => $insights,
        ]);
    }

    public function analyze(Request $request)
    {
        set_time_limit(150);

        $request->validate([
            'period' => 'required|in:day,week,month',
            'language' => 'required|in:en,id',
        ]);

        $endDate = Carbon::now();
        switch ($request->period) {
            case 'day':
                $startDate = $endDate->copy()->subDay();
                $timePeriod = 'last day';
                break;
            case 'week':
                $startDate = $endDate->copy()->subWeek();
                $timePeriod = 'last week';
                break;
            case 'month':
                $startDate = $endDate->copy()->subMonth();
                $timePeriod = 'last month';
                break;
            default:
                $startDate = $endDate->copy()->subMonth();
                $timePeriod = 'last month';
        }

        $transactions = Transaction::where('user_id', auth()->id())
            ->whereBetween('date', [$startDate, $endDate])
            ->with('category')
            ->orderBy('amount', 'desc')
            ->limit(30)
            ->get()
            ->map(function ($transaction) {
                return [
                    'date' => $transaction->date->format('Y-m-d'),
                    'description' => $transaction->description,
                    'amount' => $transaction->amount,
                    'currency' => $transaction->currency,
                    'category' => $transaction->category->name ?? 'Uncategorized',
                ];
            })
            ->toArray();

        $this->analysisService->analyzeTransactions(
            $transactions,
            $timePeriod,
            $request->language,
            $request->user()->id,
            $startDate->format('Y-m-d'),
            $endDate->format('Y-m-d')
        );

        return back()->with('status', 'Transactions has been successfully analyzed.');
    }
}
