<?php

namespace App\Traits;

use Illuminate\Support\Collection;
use App\Models\Supplier;

trait SupplierChoice
{
    public Collection $supplierChoice;

    public function mountSupplierChoice()
    {
        $this->searchSupplier();
    }

    public function searchSupplier(string $value = ''): void
    {
        $selected = Supplier::where('id', intval($this->supplier_id ?? ''))->get();
        $this->supplierChoice = Supplier::query()
            ->filterLike('name', $value)
            ->isActive()
            ->take(20)
            ->orderBy('name')
            ->get()
            ->merge($selected);
    }
}
