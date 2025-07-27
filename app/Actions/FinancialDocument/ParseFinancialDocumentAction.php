<?php

namespace App\Actions\FinancialDocument;

use App\Models\Transaction;
use App\Services\OpenAiTransactionParserService;
use App\Services\PdfToTextService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;

class ParseFinancialDocumentAction
{
    public function __construct(
        protected PdfToTextService               $pdf,
        protected OpenAiTransactionParserService $ai,
    ) {}

    /**
     * Extracts text, lets OpenAI turn it into rows, and saves them.
     *
     * @return Collection<Transaction>
     */
    public function execute(UploadedFile $file): Collection
    {
        $rawText = $this->pdf->extract($file->getPathname());

        $rows = $this->ai->parse($rawText);

        return collect($rows)->map(fn (array $row) => Transaction::create([
            'occurred_at'  => $row['date']        ?? null,
            'description' => $row['description'] ?? 'Unknown',
            'amount'      => $row['amount']      ?? 0,
            'currency'    => $row['currency']    ?? 'Unknown',
            'category'    => $row['category']    ?? 'Other',
            'user_id'     => auth()->id(),
        ]));
    }
}
