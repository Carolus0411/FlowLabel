<?php

namespace App\Traits;

use Illuminate\Support\Collection;
use App\Models\ServiceCharge;

trait ServiceChargeChoice
{
    public Collection $serviceChargeChoice;

    public function mountServiceChargeChoice()
    {
        $this->searchServiceCharge();
    }

    public function searchServiceCharge(string $value = ''): void
    {
        $selected = ServiceCharge::where('id', intval($this->service_charge_id ?? ''))->get();
        $this->serviceChargeChoice = ServiceCharge::query()
            ->filterLike('name', $value)
            ->where('is_active', 1)
            ->take(20)
            ->orderBy('name')
            ->get()
            ->merge($selected);
    }
}
