<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use App\Models\PurchaseSettlement;

class PurchaseSettlementDetailCheck implements ValidationRule
{
    protected PurchaseSettlement $purchaseSettlement;

    public function __construct(PurchaseSettlement $purchaseSettlement)
    {
        $this->purchaseSettlement = $purchaseSettlement;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($this->purchaseSettlement->details()->count() == 0) {
            $fail('The :attribute is required.');
        }
    }
}
