<?php

namespace App\Traits;

use Illuminate\Support\Collection;
use App\Models\BankAccount;

trait BankAccountChoice
{
    public Collection $bankAccountChoice;

    public function mountBankAccountChoice()
    {
        $this->searchBankAccount();
    }

    public function searchBankAccount(string $value = ''): void
    {
        $selected = BankAccount::where('id', intval($this->bank_account_id ?? ''))->get();
        $this->bankAccountChoice = BankAccount::query()
            ->filterLike('name', $value)
            ->isActive()
            ->take(20)
            ->orderBy('name')
            ->get()
            ->merge($selected);
    }
}
