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

    public function searchCashAccount(string $value = ''): void
    {
        $selected = CashAccount::where('id', intval($this->cash_account_id ?? ''))->get();
        $this->cashAccountChoice = CashAccount::query()
            ->filterLike('name', $value)
            ->isActive()
            ->take(20)
            ->orderBy('name')
            ->get()
            ->merge($selected);
    }
}
