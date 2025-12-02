<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use App\Models\BankOut;

class BankOutDetailCheck implements ValidationRule
{
    public function __construct(BankOut $bankOut)
    {
        $this->bankOut = $bankOut;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($this->bankOut->details()->count() == 0) {
            $fail('The :attribute is required.');
        }
    }
}
