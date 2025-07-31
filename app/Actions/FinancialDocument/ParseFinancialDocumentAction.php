<?php

namespace App\Actions\FinancialDocument;

use App\Models\Transaction;
use App\Models\Category;
use App\Services\OpenAiTransactionParserService;
use App\Services\PdfToTextService;
use App\Services\CsvToTextService;
use App\Services\ExcelToTextService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Str;

class ParseFinancialDocumentAction
{
    public function __construct(
        protected PdfToTextService               $pdf,
        protected CsvToTextService               $csv,
        protected ExcelToTextService             $excel,
        protected OpenAiTransactionParserService $ai,
    ) {}

    /**
     * Extracts text, lets OpenAI turn it into rows, and saves them.
     *
     * @return Collection<Transaction>
     */
    public function execute(UploadedFile $file): Collection
    {
        $extension = strtolower($file->getClientOriginalExtension());

        $rawText = match ($extension) {
            'pdf' => $this->pdf->extract($file->getPathname()),
            'csv' => $this->csv->extract($file->getPathname()),
            'xls', 'xlsx' => $this->excel->extract($file->getPathname()),
            default => throw new \InvalidArgumentException("Unsupported file type: {$extension}"),
        };

        $rows = $this->ai->parse($rawText);

        return collect($rows)->map(function (array $row) {
            $categoryId = Category::where('name', $row['category'] ?? 'Other')->value('id');

            return Transaction::create([
                'date'        => $row['date']        ?? null,
                'description' => $row['description'] ?? 'Unknown',
                'merchant_key' => $this->merchantKey($row['description'] ?? null),
                'amount'      => $row['amount']      ?? 0,
                'currency'    => $row['currency']    ?? 'Unknown',
                'category_id' => $categoryId,
                'user_id'     => auth()->id(),
            ]);
        });
    }

    private function merchantKey(?string $description): ?string
    {
        if (!$description) {
            return null;
        }

        $clean = strtolower($description);

        $clean = preg_replace('/[0-9•+*\-\\s]+/', '', $clean);

        return Str::limit(trim($clean), 60, '');
    }
}
