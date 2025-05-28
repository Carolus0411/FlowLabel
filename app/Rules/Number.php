<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use App\Helpers\Cast;

class Number implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (Cast::number($value) <= 0) {
            $fail('The :attribute must greather than 0');
        }
    }
}
