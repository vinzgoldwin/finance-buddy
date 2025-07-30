<?php

namespace App\Services;

use Illuminate\Support\Facades\File;

class CsvToTextService
{
    /**
     * Extract text from a CSV file.
     */
    public function extract(string $path): string
    {
        $content = File::get($path);

        $content = str_replace(["\r\n", "\r"], "\n", $content);

        return $content;
    }
}
