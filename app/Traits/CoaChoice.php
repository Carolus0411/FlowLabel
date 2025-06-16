<?php

namespace App\Traits;

use Illuminate\Support\Collection;
use App\Models\Coa;

trait CoaChoice
{
    public Collection $coaChoice;

    public function mountCoaChoice()
    {
        $this->searchCoa();
    }

    public function searchCoa(string $value = ''): void
    {
        $selected = Coa::where('code', $this->coa_code)->get();
        $this->coaChoice = Coa::query()
            ->filterLike('name', $value)
            ->isActive()
            ->take(20)
            ->get()
            ->merge($selected);
    }
}
