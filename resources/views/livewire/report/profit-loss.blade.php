<?php

use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Session;
use Livewire\Volt\Component;
use Mary\Traits\Toast;
use App\Helpers\Cast;
use App\Helpers\TrialBalance;
use App\Models\Coa;
use App\Models\Balance;

new class extends Component {
    use Toast;

    #[Session(key: 'pl_period1')]
    public $period1 = '';

    #[Session(key: 'pl_period2')]
    public $period2 = '';

    public $scheme = '';
    public $selected = '';
    public $drawer = false;

    public function mount(): void
    {
        Gate::authorize('view profit-loss');

        if (empty($this->period1)) {
            $this->period1 = date('Ym');
        }

        if (empty($this->period2)) {
            $this->period2 = date('Ym');
        }

        $scheme = '
        [
            {
                "code" : "1",
                "name" : "Sales Revenue",
                "type" : "title"
            },
            {
                "code" : "2",
                "type" : "group",
                "detail" : [
                    {
                        "code" : "411",
                        "name" : "Sales Warehouse",
                        "op" : "+"
                    },
                    {
                        "code" : "421",
                        "name" : "Sales Handling",
                        "op" : "+"
                    },
                    {
                        "code" : "422",
                        "name" : "Sales Freight",
                        "op" : "+"
                    },
                    {
                        "code" : "423",
                        "name" : "Sales Document Fee",
                        "op" : "+"
                    },
                    {
                        "code" : "425",
                        "name" : "Sales Trucking",
                        "op" : "+"
                    },
                    {
                        "code" : "426",
                        "name" : "Sales Logistic",
                        "op" : "+"
                    },
                    {
                        "code" : "427",
                        "name" : "Sales Transfer Edi",
                        "op" : "+"
                    },
                    {
                        "code" : "428",
                        "name" : "Sales Transfer Edi",
                        "op" : "+"
                    },
                    {
                        "code" : "429",
                        "name" : "Sales Other",
                        "op" : "+"
                    }
                ]
            },
            {
                "code" : "3",
                "name" : "Sales Revenue Total",
                "type" : "total",
                "formula" : "$2"
            },
            {
                "code" : "4",
                "type" : "newline"
            },
            {
                "code" : "5",
                "name" : "Cost Of Sales",
                "type" : "title"
            },
            {
                "code" : "6",
                "type" : "group",
                "detail" : [
                    {
                        "code" : "511",
                        "name" : "Cost Of Warehouse",
                        "op" : "-"
                    },
                    {
                        "code" : "521",
                        "name" : "Cost Of Handling",
                        "op" : "-"
                    },
                    {
                        "code" : "522",
                        "name" : "Cost Of Freight",
                        "op" : "-"
                    },
                    {
                        "code" : "523",
                        "name" : "Cost Of Document Fee",
                        "op" : "-"
                    },
                    {
                        "code" : "525",
                        "name" : "Cost Of Trucking",
                        "op" : "-"
                    },
                    {
                        "code" : "526",
                        "name" : "Cost Of Logistic",
                        "op" : "-"
                    },
                    {
                        "code" : "527",
                        "name" : "Cost Of Transfer Edi",
                        "op" : "-"
                    },
                    {
                        "code" : "528",
                        "name" : "Cost Of Insurance",
                        "op" : "-"
                    },
                    {
                        "code" : "529",
                        "name" : "Other Cost",
                        "op" : "-"
                    }
                ]
            },
            {
                "code" : "7",
                "name" : "Cost Of Sales Total",
                "type" : "total",
                "formula" : "$6"
            },
            {
                "code" : "8",
                "type" : "newline"
            },
            {
                "code" : "9",
                "name" : "Gross Profit",
                "type" : "total",
                "formula" : "$2+$6"
            },
            {
                "code" : "10",
                "type" : "newline"
            },
            {
                "code" : "11",
                "name" : "General & Admin Expenses",
                "type" : "title"
            },
            {
                "code" : "12",
                "type" : "group",
                "detail" : [
                    {
                        "code" : "529",
                        "name" : "Other Cost",
                        "op" : "-"
                    }
                ]
            }
        ]';

        // $this->scheme = [
        //     [
        //         'code' => '1',
        //         'name' => 'Sales Revenue',
        //         'type' => 'title',
        //     ],
        //     [
        //         'code' => '2',
        //         'type' => 'group',
        //         'detail' => [
        //             ['code' => '411', 'name' => 'Sales Warehouse', 'op' => '+'],
        //             ['code' => '421', 'name' => 'Sales Handling', 'op' => '+'],
        //             ['code' => '422', 'name' => 'Sales Freight', 'op' => '+'],
        //             ['code' => '423', 'name' => 'Sales Document Fee', 'op' => '+'],
        //             ['code' => '425', 'name' => 'Sales Trucking', 'op' => '+'],
        //             ['code' => '426', 'name' => 'Sales Logistic', 'op' => '+'],
        //             ['code' => '427', 'name' => 'Sales Transfer Edi', 'op' => '+'],
        //         ]
        //     ],
        //     [
        //         'code' => '3',
        //         'name' => 'Total Sales Revenue',
        //         'type' => 'total',
        //         'formula' => '2',
        //     ],
        //     ['type' => 'newline']
        // ];

        $this->scheme = json_decode($scheme, TRUE);
    }

    public function getCoa()
    {
        return Coa::query()
        ->with('journalDetails')
        ->isActive()
        ->orderBy('code')
        ->get();
    }

    public function with(): array
    {
        return [
            'coas' => $this->getCoa(),
        ];
    }

    public function search(): void
    {
        $data = $this->validate([
            'period1' => 'required',
            'period2' => 'required',
        ]);

        $this->drawer = false;
    }

    public function clear(): void
    {
        $this->period1 = date('Ym');
        $this->period2 = date('Ym');

        $this->success('Filters cleared.');
        $this->reset(['coa_code']);
        $this->drawer = false;
    }
}; ?>
@php
$configMonth = [
    'plugins' => [
        [
            'monthSelectPlugin' => [
                'dateFormat' => 'Ym',
                'altFormat' => 'F Y',
            ]
        ]
    ]
];
@endphp
<div
    x-data="{
        selected : $wire.entangle('selected'),
        select : function ($id) {
            if (this.selected == $id) {
                this.selected = '';
            } else {
                this.selected = $id
            }
        },
    }"
>
    <x-header
        title="Trial Balance" subtitle="Period : {{ \App\Helpers\Cast::monthForHuman($period1).' - '.\App\Helpers\Cast::monthForHuman($period2) }}"
        separator
        progress-indicator
    >
        <x-slot:actions>
            <x-button label="Filters" @click="$wire.drawer = true" icon="o-funnel" />
        </x-slot:actions>
    </x-header>

    <x-card wire:loading.class="bg-slate-200/50 text-slate-400">
        <div class="overflow-x-auto">
            <table class="table table-sm">
            <tbody>

            @foreach ($scheme as $data)

                {{-- TITLE --}}
                @if ($data['type'] == 'title')
                <tr class="hover:bg-base-200">
                    <td colspan="10" class="font-semibold">{{ $data['name'] }}</td>
                </tr>
                @endif

                {{-- DETAIL --}}
                @if ($data['type'] == 'group')
                    @php
                    $total = 0;
                    @endphp
                    @foreach ($data['detail'] as $detail)
                    @php
                    $balance = TrialBalance::get($detail['code'], $period1, $period2);
                    $ending = $balance->ending ?? 0;
                    if ($detail['op'] == '+') $ending = abs($ending);
                    if ($detail['op'] == '-') $ending = abs($ending) * -1;
                    $total = $total + $ending;
                    @endphp
                    <tr class="hover:bg-base-200">
                        <td class="lg:w-[100px]">{{ $detail['code'] }}</td>
                        <td>{{ $detail['name'] }}</td>
                        <td class="w-[150px] text-right">{{ Cast::money2($ending) }}</td>
                    </tr>
                    @endforeach
                    @php
                    $code = $data['code'];
                    $_VARS['$'.$code] = $total;
                    @endphp
                @endif

                {{-- TOTAL --}}
                @if ($data['type'] == 'total')
                @php
                $formula = str_replace(array_keys($_VARS), array_values($_VARS), $data['formula']);
                $total = @eval('return ' . $formula.';');
                // @dump($data['formula'] .' | '. $formula)
                @endphp
                <tr class="hover:bg-base-200">
                    <td>&nbsp;</td>
                    <td>{{ $data['name'] }}</td>
                    <td class="w-[150px] text-right">{{ Cast::money2($total) }}</td>
                </tr>
                @endif

                {{-- NEWLINE --}}
                @if ($data['type'] == 'newline')
                <tr class="hover:bg-base-200">
                    <td colspan="10">&nbsp;</td>
                </tr>
                @endif

            @endforeach

            </tbody>
            </table>
        </div>
    </x-card>

    {{-- FILTER DRAWER --}}
    <x-search-drawer>
        <div class="space-y-4 lg:space-y-0 lg:grid grid-cols-2 gap-4">
            <x-datepicker label="" wire:model="period1" icon-right="o-calendar" :config="$configMonth" class="cursor-pointer" />
            <x-datepicker label="" wire:model="period2" icon-right="o-calendar" :config="$configMonth" class="cursor-pointer" />
        </div>
    </x-search-drawer>
</div>
