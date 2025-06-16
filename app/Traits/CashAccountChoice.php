<?php

namespace App\Traits;

use Illuminate\Support\Collection;
use App\Models\CashAccount;

trait CashAccountChoice
{
    public Collection $cashAccountChoice;

    public function mountCashAccountChoice()
    {
        $this->searchCashAccount();
    }

    public function searchCashAccount(string $value = '', string $key = 'cash_account_id'): void
    {
        $selected = CashAccount::where('id', intval($this->{$key} ?? ''))->get();
        $this->cashAccountChoice = CashAccount::query()
            ->filterLike('name', $value)
            ->isActive()
            ->take(20)
            ->get()
            ->merge($selected);
    }
}
