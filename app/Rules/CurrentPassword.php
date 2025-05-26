<?php

namespace App\Rules;
use Illuminate\Support\Facades\Hash;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class CurrentPassword implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ( ! Hash::check($value, auth()->user()->password)) {
            $fail('The :attribute is not valid.');
        }
    }
}
