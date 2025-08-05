<?php

namespace App\Http\Controllers;

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
        return Inertia::render('AIAdvisor');
    }

    /**
     * Analyze transactions and provide insights
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function analyze(Request $request)
    {
        $request->validate([
            'period' => 'required|in:week,month,quarter,year',
            'language' => 'required|in:en,id',
        ]);

        // Determine date range based on period
        $endDate = Carbon::now();
        switch ($request->period) {
            case 'week':
                $startDate = $endDate->copy()->subWeek();
                $timePeriod = 'last week';
                break;
            case 'month':
                $startDate = $endDate->copy()->subMonth();
                $timePeriod = 'last month';
                break;
            case 'quarter':
                $startDate = $endDate->copy()->subQuarter();
                $timePeriod = 'last quarter';
                break;
            case 'year':
                $startDate = $endDate->copy()->subYear();
                $timePeriod = 'last year';
                break;
            default:
                $startDate = $endDate->copy()->subMonth();
                $timePeriod = 'last month';
        }

        // Get transactions for the user within the date range
        $transactions = Transaction::where('user_id', auth()->id())
            ->whereBetween('date', [$startDate, $endDate])
            ->with('category')
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

        // For demonstration purposes, we'll return sample data
        // In a real implementation, you would call the AI service:
        // $insights = $this->analysisService->analyzeTransactions(
        //     $transactions,
        //     $timePeriod,
        //     $request->language
        // );

        // Sample insights for demonstration
        $insights = [
            'spending_insights' => "I noticed you've been spending a bit more on dining out lately. Consider cooking at home more often to save some money.",
            'savings_recommendations' => "Your savings rate is looking good! Try to set aside a fixed amount each month for your emergency fund.",
            'budgeting_assistance' => "You're doing well with your budget. Consider allocating 10% of your income to savings if you're not already doing so.",
            'financial_health' => "Overall, your financial health looks solid. Keep up the good work on tracking your expenses!"
        ];

        return response()->json($insights);
    }
}