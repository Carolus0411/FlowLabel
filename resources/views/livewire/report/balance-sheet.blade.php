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

    #[Session(key: 'bs_period')]
    public $period = '';

    public $scheme = [];
    public $selected = '';
    public $drawer = false;

    public function mount(): void
    {
        Gate::authorize('view balance-sheet');

        if (empty($this->period)) {
            $this->period = date('Ym');
        }

        $scheme = '
        [
            {
                "code" : "1",
                "name" : "ASSETS",
                "type" : "title"
            },
            {
                "code" : "2",
                "name" : "Current Asset",
                "type" : "title"
            },
            {
                "code" : "3",
                "type" : "group",
                "detail" : [
                    {
                        "code" : "101",
                        "name" : "Cash"
                    },
                    {
                        "code" : "102",
                        "name" : "Bank"
                    },
                    {
                        "code" : "103",
                        "name" : "Deposits"
                    },
                    {
                        "code" : "104",
                        "name" : "Account Receivable"
                    },
                    {
                        "code" : "106",
                        "name" : "Other Receivable"
                    },
                    {
                        "code" : "107",
                        "name" : "Prepayment"
                    },
                    {
                        "code" : "108",
                        "name" : "Prepaid Taxes"
                    },
                    {
                        "code" : "109",
                        "name" : "Other Current Assets"
                    }
                ]
            },
            {
                "code" : "4",
                "name" : "Subtotal Current Assets",
                "type" : "total",
                "formula" : "{3}"
            },
            {
                "code" : "5",
                "type" : "newline"
            },
            {
                "code" : "6",
                "name" : "Non Current Assets",
                "type" : "title"
            },
            {
                "code" : "7",
                "type" : "group",
                "detail" : [
                    {
                        "code" : "110",
                        "name" : "Fixed Assets"
                    },
                    {
                        "code" : "111",
                        "name" : "Fixed Assets Accm"
                    },
                    {
                        "code" : "180",
                        "name" : "Investment & Other Assets"
                    }
                ]
            },
            {
                "code" : "8",
                "name" : "Subtotal Non Current Assets",
                "type" : "total",
                "formula" : "{7}"
            },
            {
                "code" : "9",
                "type" : "newline"
            },
            {
                "code" : "10",
                "name" : "TOTAL ASSETS",
                "type" : "total",
                "formula" : "{4}+{8}"
            },
            {
                "code" : "11",
                "type" : "newline"
            },
            {
                "code" : "12",
                "name" : "LIABILITIES",
                "type" : "title"
            },
            {
                "code" : "13",
                "name" : "Current Liabilities",
                "type" : "title"
            },
            {
                "code" : "14",
                "type" : "group",
                "detail" : [
                    {
                        "code" : "201",
                        "name" : "Account Payable"
                    },
                    {
                        "code" : "202",
                        "name" : "Short Term Loans Payable"
                    },
                    {
                        "code" : "203",
                        "name" : "Taxes Payable"
                    },
                    {
                        "code" : "204",
                        "name" : "Customer Deposits"
                    },
                    {
                        "code" : "205",
                        "name" : "Other Current Liabilities"
                    }
                ]
            },
            {
                "code" : "15",
                "name" : "Subtotal Current Liabilities",
                "type" : "total",
                "formula" : "{14}"
            },
            {
                "code" : "16",
                "name" : "Long Term Liabilities",
                "type" : "title"
            },
            {
                "code" : "17",
                "type" : "group",
                "detail" : [
                    {
                        "code" : "211",
                        "name" : "Long Term Loans Payable"
                    },
                    {
                        "code" : "212",
                        "name" : "Other Long Term Liabilities"
                    }
                ]
            },
            {
                "code" : "18",
                "name" : "Subtotal Long Term Liabilities",
                "type" : "total",
                "formula" : "{17}"
            },
            {
                "code" : "19",
                "type" : "newline"
            },
            {
                "code" : "20",
                "name" : "TOTAL LIABILITIES",
                "type" : "total",
                "formula" : "{15}+{18}"
            },
            {
                "code" : "21",
                "type" : "newline"
            },
            {
                "code" : "22",
                "name" : "EQUITY",
                "type" : "title"
            },
            {
                "code" : "23",
                "type" : "group",
                "detail" : [
                    {
                        "code" : "310-001",
                        "name" : "Paid In Capital"
                    },
                    {
                        "code" : "311-001",
                        "name" : "Retained Earnings"
                    },
                    {
                        "code" : "311-002",
                        "name" : "Dividend"
                    }
                ]
            },
            {
                "code" : "24",
                "name" : "Current Year Profit (Loss)",
                "type" : "PL-YEAR"
            },
            {
                "code" : "25",
                "name" : "Current Month Profit (Loss)",
                "type" : "PL-MONTH"
            },
            {
                "code" : "26",
                "name" : "TOTAL EQUITY",
                "type" : "total",
                "formula" : "{23}+{24}+{25}"
            },
            {
                "code" : "27",
                "type" : "newline"
            },
            {
                "code" : "28",
                "name" : "TOTAL LIABILITIES AND EQUITY",
                "type" : "total",
                "formula" : "{20}+{26}"
            }
        ]';

        $this->scheme = json_decode($scheme, TRUE);
    }

    public function with(): array
    {
        return [];
    }

    public function search(): void
    {
        $data = $this->validate([
            'period' => 'required',
        ]);

        $this->drawer = false;
    }

    public function clear(): void
    {
        $this->period = date('Ym');

        $this->success('Filters cleared.');
        $this->reset([]);
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
<div>
    <x-header
        title="Balance Sheet" subtitle="Period : {{ Cast::monthForHuman($period) }}"
        separator
        progress-indicator
    >
        <x-slot:actions>
            <x-button label="Filters" @click="$wire.drawer = true" icon="o-funnel" />
        </x-slot:actions>
    </x-header>

    <x-card wire:loading.class="bg-slate-200/50 text-slate-400">
        <div class="overflow-x-auto">
            <table class="table table-sm w-auto lg:min-w-2xl">
            <thead>
            <tr>
                <th>Code</th>
                <th>Description</th>
                <th class="text-right">Amount</th>
                <th class="text-right">Total</th>
            </tr>
            </thead>
            <tbody>

            @foreach ($scheme as $data)

                {{-- TITLE --}}
                @if ($data['type'] == 'title')
                <tr class="hover:bg-base-200">
                    <td>&nbsp;</td>
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
                    $balance = TrialBalance::get($detail['code'], $period, $period);
                    $ending = $balance->ending ?? 0;

                    $total = $total + $ending;
                    @endphp
                    <tr class="hover:bg-base-200">
                        <td class="lg:w-[100px]">{{ $detail['code'] }}</td>
                        <td>{{ $detail['name'] }}</td>
                        <td class="w-[150px] text-right">{{ Cast::absMoney($ending) }}</td>
                        <td>&nbsp;</td>
                    </tr>
                    @endforeach
                    @php
                    $code = $data['code'];
                    $_VARS['{'.$code.'}'] = $total;
                    @endphp
                @endif

                {{-- PL-YEAR --}}
                @if ($data['type'] == 'PL-YEAR')
                @php
                $total = TrialBalance::currentYearProfit($period);
                $code = $data['code'];
                $_VARS['{'.$code.'}'] = $total;
                @endphp
                <tr class="hover:bg-base-200">
                    <td>&nbsp;</td>
                    <td>{{ $data['name'] }}</td>
                    <td class="w-[150px] text-right">{{ Cast::absMoney($total) }}</td>
                    <td>&nbsp;</td>
                </tr>
                @endif

                {{-- PL-MONTH --}}
                @if ($data['type'] == 'PL-MONTH')
                @php
                $total = TrialBalance::currentMonthProfit($period);
                $code = $data['code'];
                $_VARS['{'.$code.'}'] = $total;
                @endphp
                <tr class="hover:bg-base-200">
                    <td>&nbsp;</td>
                    <td>{{ $data['name'] }}</td>
                    <td class="w-[150px] text-right">{{ Cast::absMoney($total) }}</td>
                    <td>&nbsp;</td>
                </tr>
                @endif

                {{-- TOTAL --}}
                @if ($data['type'] == 'total')
                @php
                $formula = str_replace(array_keys($_VARS), array_values($_VARS), $data['formula']);
                $total = @eval('return ' . $formula.';');
                $code = $data['code'];
                $_VARS['{'.$code.'}'] = $total;
                @endphp
                <tr class="hover:bg-base-200">
                    <td>&nbsp;</td>
                    <td>{{ $data['name'] }}</td>
                    <td>&nbsp;</td>
                    <td class="w-[150px] text-right">{{ Cast::absMoney($total) }}</td>
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
        <x-datepicker label="" wire:model="period" icon-right="o-calendar" :config="$configMonth" class="cursor-pointer" />
    </x-search-drawer>
</div>
