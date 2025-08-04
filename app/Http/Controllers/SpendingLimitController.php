<?php

namespace App\Http\Controllers;

use App\Models\SpendingLimit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SpendingLimitController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0',
            'interval' => 'required|in:daily,weekly,monthly',
        ]);

        $spendingLimit = Auth::user()->spendingLimits()->updateOrCreate(
            ['user_id' => Auth::id()],
            [
                'amount' => $request->amount,
                'interval' => $request->interval,
            ]
        );

        return response()->json($spendingLimit);
    }

    public function show()
    {
        $spendingLimit = Auth::user()->spendingLimits()->first();
        
        return response()->json($spendingLimit ?? [
            'amount' => 0,
            'interval' => 'monthly',
        ]);
    }
}
