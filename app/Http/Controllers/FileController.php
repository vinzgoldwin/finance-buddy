<?php

namespace App\Http\Controllers;

use App\Actions\FinancialDocument\ParseFinancialDocumentAction;
use App\Rules\PdfMaxPages;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Str;

class FileController extends Controller
{
    /**
     * Show the "upload statement" page.
     */
    public function create(): Response
    {
        return Inertia::render('UploadFile');
    }

    /**
     * Handle an uploaded file (PDF, CSV, Excel), send it to OpenAI, and persist transactions.
     */
    public function store(Request $request, ParseFinancialDocumentAction $parseDoc): RedirectResponse
    {
        set_time_limit(300);

        $validated = $request->validate([
            'file' => [
                'required',
                'file',
                'mimes:pdf,csv,xls,xlsx',
                'max:5120'
            ],
        ]);

        if ($request->file('file')->getClientOriginalExtension() === 'pdf') {
            $request->validate([
                'file' => [new PdfMaxPages(10)],
            ]);
        }

        $file = $validated['file'];
        $importedTransaction = $parseDoc->execute($file);

        return back()->with([
            'status' => __(':count transactions imported', ['count' => $importedTransaction->count()]),
        ]);
    }
}
