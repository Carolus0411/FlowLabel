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
            ['key' => 'order_total', 'label' => 'Order Total'],
        ];
    }

    public function shippings()
    {
        return Shipping::query()
        ->whereDateBetween('DATE(shipping.created_at)', $this->date1, $this->date2)
        ->selectRaw('COUNT(shipping.id) as order_total')
        ->orderBy(DB::raw('COUNT(shipping.id)'),'desc')
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
    <x-card title="Top Volume" separator>
        <x-table :headers="$headers" :rows="$shippings" no-headers striped>
            @scope('cell_order_total', $shipping)
            <span class="font-bold pr-2">{{ $shipping['order_total'] }}</span>Shipment
            @endscope
        </x-table>
    </x-card>
</div>
