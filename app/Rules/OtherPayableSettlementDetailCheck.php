<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use App\Models\OtherPayableSettlement;

class OtherPayableSettlementDetailCheck implements ValidationRule
{
    public function __construct(OtherPayableSettlement $otherPayableSettlement)
    {
        $this->otherPayableSettlement = $otherPayableSettlement;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($this->otherPayableSettlement->details()->count() == 0) {
            $fail('The :attribute is required.');
        }
    }
}
