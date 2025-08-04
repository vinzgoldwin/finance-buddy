<?php

namespace App\Actions\FinancialDocument;

use App\Models\Transaction;
use App\Models\Category;
use App\Services\OpenAiTransactionParserService;
use App\Services\PdfToTextService;
use App\Services\CsvToTextService;
use App\Services\ExcelToTextService;
use App\Services\TransactionAnalyticsService;
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
        protected TransactionAnalyticsService    $analytics,
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

            $tx = Transaction::create([
                'date'        => $row['date']        ?? null,
                'description' => $row['description'] ?? 'Unknown',
                'merchant_key' => $this->merchantKey($row['description'] ?? null),
                'amount'      => $row['amount']      ?? 0,
                'currency'    => $row['currency']    ?? 'Unknown',
                'category_id' => $categoryId,
                'user_id'     => auth()->id(),
            ]);

            $this->analytics->handle($tx);

            return $tx;
        });
    }

    private function merchantKey(?string $description): ?string
    {
        if (!$description) {
            return null;
        }

        $txt = strtolower($description);

        // 1. Kill generic gateways up-front.
        foreach (['doku', 'qris', 'visa', 'mastercard', 'gopay', 'ovo'] as $gw) {
            $txt = str_replace($gw, '', $txt);
        }

        // 2. Chop off any leading separators left behind.
        $txt = ltrim($txt, "*/ \t\n\r\0\x0B");

        // 3. Split on *, /, or whitespace and pick the first non-empty piece.
        foreach (preg_split('/[\*\/\s]+/', $txt) as $part) {
            $part = preg_replace('/[^a-z]/', '', $part);   // keep letters only
            if ($part !== '') {
                return Str::limit($part, 40, '');
            }
        }

        return null; // nothing usable
    }

}
