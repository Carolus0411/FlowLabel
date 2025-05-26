<?php

use Illuminate\Support\Facades\DB;
use Livewire\Volt\Component;
use Livewire\Attributes\Reactive;
use App\Models\Shipping;

new class extends Component {

    #[Reactive]
    public $date1;

    #[Reactive]
    public $date2;

    public function mount(): void
    {

    }

    public function headers(): array
    {
        return [
            ['key' => 'country_name', 'label' => 'Country Name'],
            ['key' => 'weight_total', 'label' => 'Weight Total'],
        ];
    }

    public function shippings()
    {
        return Shipping::query()
        ->whereDateBetween('DATE(shipping.created_at)', $this->date1, $this->date2)
        ->selectRaw('SUM(shipping.chargeable_weight) as weight_total')
        ->orderBy(DB::raw('SUM(shipping.chargeable_weight)'),'desc')
        ->groupBy(['shipping.receiver_country_id','country.name'])
        ->joinReceiverCountry()
        ->limit(5)
        ->get();
    }

    public function with(): array
    {
        return [
            'headers' => $this->headers(),
            'shippings' => $this->shippings(),
        ];
    }

    public function placeholder()
    {
        return <<<'HTML'
        <div class="h-full flex gap-3 justify-center bg-indigo-50 dark:bg-gray-800 dark:text-white p-20 rounded-lg">
            <x-loading class="text-primary loading-dots" /> Loading...
        </div>
        HTML;
    }
}; ?>

<div>
    <x-card title="Top Weight" separator>
        <x-table :headers="$headers" :rows="$shippings" no-headers striped>
            @scope('cell_weight_total', $shipping)
            {{ $shipping['weight_total'] }} Kgs
            @endscope
        </x-table>
    </x-card>
</div>
