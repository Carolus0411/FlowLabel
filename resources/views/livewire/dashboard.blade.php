<?php

use Livewire\Volt\Component;
use App\Models\Shipping;
use Mary\Traits\Toast;

new class extends Component {

    use Toast;

    public ?string $date1;
    public ?string $date2;
    public ?string $show = '';

    public function mount(): void
    {
        $this->date1 = date('Y-m-01');
        $this->date2 = date('Y-m-t');
    }

    public function with(): array
    {
        $date1 = $this->date1;
        $date2 = $this->date2;

        return [
            'allOrder' => 0,
            'inbound' => $status['200'] ?? 0,
            'outbound' => $status['300'] ?? 0,
            'delivered' => $status['400'] ?? 0,
        ];
    }

    public function filter(): void
    {
        $this->show = $this->date1 . ' - ' . $this->date2;
    }
}; ?>
<div>
    @php
    $configDate = [
        'altInput' => true,
        'altFormat' => 'F j, Y',
        'dateFormat' => 'Y-m-d',
    ];
    @endphp

    <x-header title="Dashboard" subtitle="Application Dashboard" separator progress-indicator>
        <x-slot:middle class="!justify-end">
            <div class="flex items-center gap-3">
                <x-datepicker label="" wire:model.live="date1" icon-right="o-calendar" :config="$configDate" />
                <x-datepicker label="" wire:model.live="date2" icon-right="o-calendar" :config="$configDate" />
            </div>
        </x-slot:middle>
        <x-slot:actions>
            <x-button label="Filter" wire:click="filter" spinner="filter" responsive icon="o-funnel" class="btn-primary" />
        </x-slot:actions>
    </x-header>

    <div class="space-y-5">
        <div class="bg-base-50 grid lg:grid-cols-5 gap-5">
            <x-stat title="Order Received" :value="$allOrder" icon="o-envelope" tooltip="All order received" class="shadow-xs border border-slate-200" />
            <x-stat title="Inbound" :value="$inbound" icon="o-arrow-right-end-on-rectangle" tooltip="Inbounded" class="shadow-xs border border-slate-200" color="text-pink-500" />
            <x-stat title="Outbound" :value="$outbound" icon="o-arrow-right-start-on-rectangle" tooltip="Outbound" class="shadow-xs border border-slate-200" color="text-sky-500" />
            <x-stat title="Delivered" :value="$delivered" icon="o-document-check" tooltip="Delivered order" class="shadow-xs border border-slate-200" color="text-green-500" />
            <x-stat title="On Progress" value="23" icon="o-arrow-path" tooltip-bottom="On progress" class="shadow-xs border border-slate-200" color="text-yellow-500" />
        </div>

        <div class="lg:grid grid-cols-12 gap-5">
            <x-card title="Weekly Order" class="col-span-8 flex justify-items-center" shadow separator>
                <livewire:dashboard.line-chart :date1="$date1" :date2="$date2" lazy />
            </x-card>
            <x-card title="Shipping Status" class="col-span-4" shadow separator>
                <livewire:dashboard.pie-chart :date1="$date1" :date2="$date2" lazy />
            </x-card>
        </div>

        {{-- <div class="lg:grid grid-cols-2 gap-5">
            <livewire:dashboard.top-order :date1="$date1" :date2="$date2" lazy />
            <livewire:dashboard.top-weight :date1="$date1" :date2="$date2" lazy />
        </div> --}}
    </div>
</div>
