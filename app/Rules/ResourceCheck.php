<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use App\Helpers\Cast;

class ResourceCheck implements ValidationRule
{
    public function __construct(
        public string $resourceType,
    ){}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($this->resourceType == 'App\Models\CashIn' AND !\App\Models\CashIn::where('id', $value)->exists()) {
            $fail('The :attribute is invalid.');
        }
        if ($this->resourceType == 'App\Models\CashOut' AND !\App\Models\CashOut::where('id', $value)->exists()) {
            $fail('The :attribute is invalid.');
        }
        if ($this->resourceType == 'App\Models\SalesInvoice' AND !\App\Models\SalesInvoice::where('id', $value)->exists()) {
            $fail('The :attribute is invalid.');
        }
        if ($this->resourceType == 'App\Models\SalesSettlement' AND !\App\Models\SalesSettlement::where('id', $value)->exists()) {
            $fail('The :attribute is invalid.');
        }
        if ($this->resourceType == 'App\Models\Journal' AND !\App\Models\Journal::where('id', $value)->exists()) {
            $fail('The :attribute is invalid.');
        }
    }
}
