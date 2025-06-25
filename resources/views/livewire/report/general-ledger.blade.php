<?php

use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Session;
use Livewire\Volt\Component;
use Illuminate\Support\Str;
use Mary\Traits\Toast;
use App\Helpers\Cast;
use App\Traits\CoaChoice;
use App\Models\Coa;
use App\Models\Balance;

new class extends Component {
    use Toast, CoaChoice;

    #[Session(key: 'glreport_period1')]
    public $period1 = '';

    #[Session(key: 'glreport_period2')]
    public $period2 = '';

    #[Session(key: 'glreport_coa_code')]
    public $coa_code = '';

    public $drawer = false;

    public function mount(): void
    {
        Gate::authorize('view general-ledger');

        if (empty($this->date1)) {
            $this->date1 = date('Y-m-01');
            $this->date2 = date('Y-m-t');
        }

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
        ->where('code', $this->coa_code)
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
<div>
    <x-header title="General Ledger" subtitle="Period : {{ \App\Helpers\Cast::monthForHuman($period1).' - '.\App\Helpers\Cast::monthForHuman($period2) }}" separator>
        {{-- <x-slot:subtitle>
            <x-subtitle-date :date1="$date1" :date2="$date2" />
        </x-slot:subtitle> --}}
        <x-slot:actions>
            <x-button label="Filters" @click="$wire.drawer = true" icon="o-funnel" />
        </x-slot:actions>
    </x-header>

    <x-card>
        <div class="overflow-x-auto">
            <table class="table table-sm">
            <thead>
            <tr>
                <th>Date</th>
                <th>Code</th>
                <th>Reference</th>
                <th>Description</th>
                <th class="lg:w-[150px] text-right">Debit</th>
                <th class="lg:w-[150px] text-right">Credit</th>
                <th class="lg:w-[150px] text-right">Balance</th>
            </tr>
            </thead>
            <tbody>
            @php
            $balance = 0;
            @endphp
            @forelse ( $coas as $coa )
                <tr class="bg-base-200 hover:bg-base-300">
                    <td colspan="7"><span class="font-bold">{{ $coa->code }}</span>, {{ $coa->name ?? '' }}</td>
                </tr>
                @php
                $balance = 0;
                $leftBalance = \App\Helpers\TrialBalance::leftBalance( $coa->code, $period1, $period2 );
                $balance = $leftBalance->ending;
                @endphp
                <tr class="hover:bg-base-200 font-semibold">
                    <td colspan="3"></td>
                    <td>Beginning Balance</td>
                    <td class="text-right"></td>
                    <td class="text-right"></td>
                    <td class="text-right">{{ Cast::money($balance) }}</td>
                </tr>

                @php
                $journalDetails = $coa->journalDetails()->with('journal')->whereBetween('month', [$period1, $period2])->get();
                @endphp

                @forelse ( $journalDetails as $journalDetail)
                @php
                $balance = $balance + $journalDetail->debit - $journalDetail->credit;
                @endphp
                <tr class="hover:bg-base-200">
                    <td>{{ \Illuminate\Support\Carbon::parse($journalDetail->date)->format('d/m/y') }}</td>
                    <td>{{ $journalDetail->code }}</td>
                    <td>{{ $journalDetail->journal->ref_id ?? '' }}</td>
                    <td><div class="lg:max-w-[200px] truncate">{{ $journalDetail->description }}</div></td>
                    <td class="text-right">{{ Cast::money($journalDetail->debit) }}</td>
                    <td class="text-right">{{ Cast::money($journalDetail->credit) }}</td>
                    <td class="text-right">{{ Cast::money($balance) }}</td>
                </tr>
                @empty
                <tr class="hover:!bg-slate-100 dark:hover:!bg-slate-800">
                    <td></td>
                    <td></td>
                    <td></td>
                    <td colspan="4">NO TRANSACTION</td>
                </tr>
                @endforelse

                @php
                $trialBalance = \App\Helpers\TrialBalance::get($coa->code, $period1, $period2);
                @endphp
                <tr class="hover:!bg-slate-100 dark:hover:!bg-slate-800 font-semibold">
                    <td></td>
                    <td></td>
                    <td></td>
                    <td>Ending Balance</td>
                    <td></td>
                    <td></td>
                    <td class="text-right">{{ Cast::money($trialBalance->ending) }}</td>
                </tr>

            </tr>
            @empty
            <tr>
                <td colspan="4">No data found.</td>
            </tr>
            @endforelse
            {{-- <tr class="bg-gray-100">
                <td colspan="2" class="text-right font-semibold">Total</td>
                <td class="text-right font-semibold">{{ \App\Helpers\Cast::money($sumDebit) }}</td>
                <td class="text-right font-semibold">{{ \App\Helpers\Cast::money($sumCredit) }}</td>
                <td></td>
            </tr> --}}
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
