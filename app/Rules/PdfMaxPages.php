<?php

namespace App\Rules;

use Closure;
use Imagick;                                        // needs Imagick + Ghostscript
use Illuminate\Contracts\Validation\ValidationRule;

class PdfMaxPages implements ValidationRule
{
    public function __construct(private int $maxPages = 5) {}

    /** @inheritDoc */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $imagick = new Imagick();
        $imagick->pingImage($value->getPathname());

        if ($imagick->getNumberImages() > $this->maxPages) {
            $fail("The uploaded PDF may contain at most {$this->maxPages} pages.");
        }
    }
}
