<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use App\Models\CashIn;

class CashInDetailCheck implements ValidationRule
{
    public function __construct(CashIn $cashIn)
    {
        $this->cashIn = $cashIn;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($this->cashIn->details()->count() == 0) {
            $fail('The :attribute is required.');
        }
    }
}
