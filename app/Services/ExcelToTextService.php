<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class ExcelToTextService
{
    /**
     * Extract text from an Excel file.
     */
    public function extract(string $path): string
    {
        $spreadsheet = IOFactory::load($path);
        
        $text = '';
        foreach ($spreadsheet->getAllSheets() as $worksheet) {
            $text .= "Sheet: " . $worksheet->getTitle() . "\n";
            
            $highestRow = $worksheet->getHighestRow();
            $highestColumn = $worksheet->getHighestColumn();
            
            for ($row = 1; $row <= $highestRow; $row++) {
                $rowData = [];
                for ($col = 'A'; $col <= $highestColumn; $col++) {
                    $cell = $worksheet->getCell($col . $row);
                    $rowData[] = $cell->getValue();
                }
                $text .= implode(',', $rowData) . "\n";
            }
            $text .= "\n";
        }
        
        return $text;
    }
}