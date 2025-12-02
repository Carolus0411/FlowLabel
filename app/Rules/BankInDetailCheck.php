<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use App\Models\BankIn;

class BankInDetailCheck implements ValidationRule
{
    public function __construct(BankIn $bankIn)
    {
        $this->bankIn = $bankIn;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($this->bankIn->details()->count() == 0) {
            $fail('The :attribute is required.');
        }
    }
}
