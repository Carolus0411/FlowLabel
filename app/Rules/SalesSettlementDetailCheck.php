<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use App\Models\SalesSettlement;

class SalesSettlementDetailCheck implements ValidationRule
{
    public function __construct(SalesSettlement $salesSettlement)
    {
        $this->salesSettlement = $salesSettlement;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($this->salesSettlement->details()->count() == 0) {
            $fail('The :attribute is required.');
        }
    }
}
