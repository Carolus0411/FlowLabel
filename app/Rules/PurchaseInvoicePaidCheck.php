<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use App\Models\PurchaseInvoice;
use App\Helpers\Cast;

class PurchaseInvoicePaidCheck implements ValidationRule
{
    protected ?PurchaseInvoice $purchaseInvoice;

    public function __construct(string $code)
    {
        $this->purchaseInvoice = PurchaseInvoice::where('code', $code)->first();
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $amount = Cast::number($value);
        if ($this->purchaseInvoice && $amount > Cast::number($this->purchaseInvoice->balance_amount)) {
            $fail('The :attribute must be less than or equal to invoice balance amount.');
        }
    }
}
