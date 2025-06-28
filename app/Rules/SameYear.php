<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class SameYear implements ValidationRule
{
    public function __construct($otherPeriod)
    {
        $this->otherYear = substr($otherPeriod, 0, 4);
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $year = substr($value, 0, 4);

        if ($year != $this->otherYear) {
            $fail('The year must be the same.');
        }
    }
}
