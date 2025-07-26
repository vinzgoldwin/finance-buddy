<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\PdfToText\Pdf;

class StatementController extends Controller
{
    /**
     * Display the upload statement page.
     */
    public function create(): Response
    {
        return Inertia::render('UploadStatement');
    }

    /**
     * Handle the uploaded statement PDF.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'statement' => ['required', 'file', 'mimes:pdf'],
        ]);

        $file = $validated['statement'];

        // Extract text from the PDF. Additional parsing logic can be placed here.
        $text = Pdf::getText($file->getPathname());

        // TODO: Process \$text to create transactions or other records.

        // Store the uploaded file for reference.
        $file->store('statements');

        return back()->with('status', 'Statement uploaded');
    }
}
