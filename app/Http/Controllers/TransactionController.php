<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Transaction;
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

        $monthOptions = collect(range(0, 5))->map(function ($i) {
            $date = now()->subMonths($i);
            return [
                'value' => $date->format('Y-m'),
                'label' => $date->translatedFormat('F Y'),
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

        Transaction::create($validated);

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

        return back()->with('status', 'Transaction updated successfully.');
    }

    public function destroy(Transaction $transaction): RedirectResponse
    {
        $transaction->delete();

        return back()->with('status', 'Transaction deleted successfully.');
    }
}
