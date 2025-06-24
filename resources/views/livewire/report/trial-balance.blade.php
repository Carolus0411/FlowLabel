<?php

use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Session;
use Livewire\Volt\Component;
use Mary\Traits\Toast;
use App\Helpers\Cast;
use App\Helpers\TrialBalance;
use App\Traits\CoaChoice;
use App\Models\Coa;
use App\Models\Balance;

new class extends Component {
    use Toast, CoaChoice;

    #[Session(key: 'trialbalance_period1')]
    public $period1 = '';

    #[Session(key: 'trialbalance_period2')]
    public $period2 = '';

    #[Session(key: 'trialbalance_coa_code')]
    public $coa_code = '';

    public $selected = '';
    public $drawer = false;

    public function mount(): void
    {
        Gate::authorize('view trial-balance');

        if (empty($this->period1)) {
            $this->period1 = date('Ym');
        }

        if (empty($this->period2)) {
            $this->period2 = date('Ym');
        }
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
            <thead>
            <tr>
                <th colspan="2">Account</th>
                <th class="text-right"><span class="text-slate-400 font-normal">Beginning</span><br>Balance</th>
                <th class="text-right"><span class="text-slate-400 font-normal">Transaction</span><br>Debit</th>
                <th class="text-right"><span class="text-slate-400 font-normal">Transaction</span><br>Credit</th>
                <th class="text-right"><span class="text-slate-400 font-normal">Ending</span><br>Balance</th>
            </tr>
            </thead>
            <tbody>
            @php
            $sumBeginning = 0;
            $sumTransDebit = 0;
            $sumTransCredit = 0;
            $sumEnding = 0;
            @endphp

            @forelse ($coas as $coa)

            @php
            $balance = TrialBalance::get($coa->code, $period1, $period2);
            $beginning = $balance->beginning;
            $transDebit = $balance->transDebit;
            $transCredit = $balance->transCredit;
            $ending = $balance->ending;

            $sumBeginning = bcadd($sumBeginning, $beginning, 2);
            $sumTransDebit = bcadd($sumTransDebit, $transDebit, 2);
            $sumTransCredit = bcadd($sumTransCredit, $transCredit, 2);
            $sumEnding = bcadd($sumEnding, $ending, 2);
            @endphp

            <tr :class="selected=='{{ $coa->id }}' ? 'bg-amber-100 dark:bg-amber-950 hover:!bg-amber-200 dark:hover:!bg-amber-900' : ''" class="hover:bg-base-200" @click="select('{{ $coa->id }}')">
                <td class="font-semibold w-[100px]">{{ $coa->code }}</td>
                <td>{{ $coa->name }}</td>

                <td class="text-right">{{ \App\Helpers\Cast::money($beginning) }}</td>
                <td class="text-right">{{ \App\Helpers\Cast::money($transDebit) }}</td>
                <td class="text-right">{{ \App\Helpers\Cast::money($transCredit) }}</td>
                <td class="text-right">{{ \App\Helpers\Cast::money($ending) }}</td>
            </tr>

            @empty
            <tr>
                <td colspan="8" class="text-center">NO DATA FOUND</td>
            </tr>
            @endforelse
            </tbody>

            <tfoot>
            <tr class="bg-base-200">
                <th colspan="2" class="text-right font-semibold">Total</th>
                <th class="text-right font-semibold">{{ Cast::money($sumBeginning) }}</th>
                <th class="text-right font-semibold">{{ Cast::money($sumTransDebit) }}</th>
                <th class="text-right font-semibold">{{ Cast::money($sumTransCredit) }}</th>
                <th class="text-right font-semibold">{{ Cast::money($sumEnding) }}</th>
            </tr>
            </tfoot>
            </table>
        </div>
    </x-card>

    {{-- FILTER DRAWER --}}
    <x-search-drawer>
        <div class="space-y-4 lg:space-y-0 lg:grid grid-cols-2 gap-4">
            <x-datepicker label="" wire:model="period1" icon-right="o-calendar" :config="$configMonth" class="cursor-pointer" />
            <x-datepicker label="" wire:model="period2" icon-right="o-calendar" :config="$configMonth" class="cursor-pointer" />
        </div>
        <x-choices-offline
            label="Account"
            :options="\App\Models\Coa::query()->isActive()->orderBy('code')->get()"
            wire:model="coa_code"
            option-value="code"
            option-label="full_name"
            single
            searchable
            clearable
            placeholder="-- Select --"
        />
    </x-search-drawer>
</div>
