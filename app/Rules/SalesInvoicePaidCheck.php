<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use App\Models\SalesInvoice;

class SalesInvoicePaidCheck implements ValidationRule
{
    public function __construct(string $code)
    {
        $this->salesInvoice = SalesInvoice::where('code', $code)->first();
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!empty($this->salesInvoice->balance_amount) AND $value > $this->salesInvoice->balance_amount) {
            $fail('The :attribute must be less than invoice balance amount.');
        }
    }
}
