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
        $year = $request->query('year', Carbon::now()->year);
        $month = $request->query('month');

        $query = Transaction::with('category')
            ->whereYear('date', $year);

        if ($month) {
            $query->whereMonth('date', $month);
        }

        $transactions = $query->orderBy('date', 'desc')
            ->paginate(20)
            ->withQueryString();

        $categories = Category::orderBy('name')->get();

        return Inertia::render('Transactions', [
            'transactions' => $transactions,
            'categories' => $categories,
            'filters' => [
                'year' => (int) $year,
                'month' => $month ? (int) $month : null,
            ],
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
