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

    public array $data = [];

    public function with(): array
    {
        $end = date('Y-m-d');
		$start = date('Y-m-d', strtotime('-6 day', strtotime($end)));

        // $shipping = Shipping::whereDateBetween('DATE(created_at)', $start, $end)
        // ->selectRaw('date(created_at) as date, count(id) as num')
        // ->groupBy([DB::raw('date(created_at)')])
        // ->get()->pluck('num','date');

        $labels = [];
        $data = [];
        for ($i=6; $i>=0; $i-- ) {
            $labels[] = date('d-M-y', strtotime("-$i day"));
            $key = date('Y-m-d', strtotime("-$i day"));
            $data[] = $shipping[$key] ?? rand(10,20);
        }

        $this->data = [
            'type' => 'line',
            'options' => [
                'responsive' => true,
                'maintainAspectRatio' => false,
                'plugins' => [
                    'legend' => [
                        'display' => false,
                        'position' => 'right',
                        'labels' => [
                            'usePointStyle' => true,
                        ]
                    ],
                ],
                'elements' => [
                    'line' => [
                        'tension' => 0.4
                    ],
                ],
            ],
            'data' => [
                'labels' => $labels,
                'datasets' => [
                    [
                        'label' => ' # Volume',
                        'data' => $data,
                        'fill' => 'start',
                    ],
                ],
            ],
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
