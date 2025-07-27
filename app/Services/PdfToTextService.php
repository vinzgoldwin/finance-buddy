<?php

namespace App\Services;

use Spatie\PdfToText\Pdf;

class PdfToTextService
{
    public function extract(string $path): string
    {
        return Pdf::getText($path);
    }
}
