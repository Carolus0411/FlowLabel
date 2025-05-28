<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use App\Models\SalesInvoice;

class SalesOrderDetailCheck implements ValidationRule
{
    public function __construct(SalesInvoice $salesInvoice)
    {
        $this->salesInvoice = $salesInvoice;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($this->salesInvoice->details()->count() == 0) {
            $fail('The :attribute is required.');
        }
    }
}
