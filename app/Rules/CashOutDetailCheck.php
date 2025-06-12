<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use App\Models\CashOut;

class CashOutDetailCheck implements ValidationRule
{
    public function __construct(CashOut $cashOut)
    {
        $this->cashOut = $cashOut;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($this->cashOut->details()->count() == 0) {
            $fail('The :attribute is required.');
        }
    }
}
