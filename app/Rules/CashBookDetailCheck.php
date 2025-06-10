<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use App\Models\CashBook;

class CashBookDetailCheck implements ValidationRule
{
    public function __construct(CashBook $cashBook)
    {
        $this->cashBook = $cashBook;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($this->cashBook->details()->count() == 0) {
            $fail('The :attribute is required.');
        }
    }
}
