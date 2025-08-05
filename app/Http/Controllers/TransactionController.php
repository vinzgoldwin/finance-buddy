<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Transaction;
use App\Services\TransactionAnalyticsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class TransactionController extends Controller
{
    public function index(Request $request): Response
    {
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

        [$y, $m] = array_pad(explode('-', $monthStr), 2, null);
        $startDate = Carbon::createSafe((int) $y, (int) $m, 1)->startOfMonth();
        $endDate   = $startDate->copy()->endOfMonth();

        $transactions = Transaction::with('category')
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date', 'desc')
            ->paginate(20)
            ->withQueryString();

        $categories = Category::orderBy('name')->get();

        return Inertia::render('Transactions', [
            'transactions' => $transactions,
            'categories'   => $categories,
            'date'         => $monthStr,
            'monthOptions' => $monthOptions,
            'preferredCurrency' => auth()->user()->preferred_currency ?? 'IDR',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'date' => ['required', 'date'],
            'description' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric'],
            'currency' => ['required', 'string', Rule::in(['USD', 'IDR'])],
            'category_id' => ['required', 'exists:categories,id'],
        ]);

        $validated['user_id'] = auth()->id();

        $tx = Transaction::create($validated);

        app(TransactionAnalyticsService::class)->handle($tx);

        return back()->with('status', 'Transaction created successfully.');
    }

    public function update(Request $request, Transaction $transaction): RedirectResponse
    {
        $validated = $request->validate([
            'date' => ['required', 'date'],
            'description' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric'],
            'currency' => ['required', 'string', Rule::in(['USD', 'IDR'])],
            'category_id' => ['required', 'exists:categories,id'],
        ]);

        $transaction->update($validated);

        $transaction->refresh();

        app(TransactionAnalyticsService::class)->handle($transaction);

        return back()->with('status', 'Transaction updated successfully.');
    }

    public function destroy(Transaction $transaction): RedirectResponse
    {
        $transaction->delete();

        return back()->with('status', 'Transaction deleted successfully.');
    }
}
