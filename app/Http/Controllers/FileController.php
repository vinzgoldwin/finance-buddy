<?php

namespace App\Http\Controllers;

use App\Actions\FinancialDocument\ParseFinancialDocumentAction;
use App\Rules\PdfMaxPages;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FileController extends Controller
{
    /**
     * Show the “upload statement” page.
     */
    public function create(): Response
    {
        return Inertia::render('UploadFile');
    }

    /**
     * Handle an uploaded PDF, send it to OpenAI, and (later) persist transactions.
     */
    public function store(Request $request, ParseFinancialDocumentAction $parseDoc): RedirectResponse
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:pdf', 'max:5120', new PdfMaxPages(10)],
        ]);

        $file = $validated['file'];
        $importedTransaction = $parseDoc->execute($file);

        return back()->with([
            'status' => __(':count transactions imported', ['count' => $importedTransaction->count()]),
        ]);
    }
}
