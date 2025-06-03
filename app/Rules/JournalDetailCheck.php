<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use App\Models\Journal;

class JournalDetailCheck implements ValidationRule
{
    public function __construct(Journal $journal)
    {
        $this->journal = $journal;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($this->journal->details()->count() == 0) {
            $fail('The :attribute is required.');
        }
    }
}
