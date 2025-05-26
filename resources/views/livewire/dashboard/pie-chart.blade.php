<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Reactive;
use App\Models\Shipping;

new class extends Component {

    #[Reactive]
    public $date1;

    #[Reactive]
    public $date2;

    public array $data = [];

    public function mount(): void
    {

    }

    public function with(): array
    {
        // $status = Shipping::whereDateBetween('DATE(created_at)', $this->date1, $this->date2)
        // ->selectRaw('count(id) as num, status_code, status_text')
        // ->groupBy(['status_code','status_text'])
        // ->get()->pluck('num','status_code');

        $inbound = $status['200'] ?? 0;
        $outbound = $status['300'] ?? 0;
        $delivered = $status['400'] ?? 0;

        $this->data = [
            'type' => 'doughnut',
            'options' => [
                'responsive' => true,
                'maintainAspectRatio' => false,
                'plugins' => [
                    'legend' => [
                        'position' => 'left',
                        'labels' => [
                            'usePointStyle' => true,
                        ],
                    ],
                ],
            ],
            'data' => [
                'labels' => ['Inbound', 'Outbound', 'Delivered'],
                'datasets' => [
                    [
                        'label' => ' # Shipping Orders',
                        'data' => [$inbound, $outbound, $delivered],
                    ],
                ],
            ]
        ];

        return [];
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

    <x-chart wire:model="data" class="w-full h-[250px]" />

</div>
