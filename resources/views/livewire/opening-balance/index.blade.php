<?php

use Illuminate\Support\Facades\Gate;
use Livewire\Volt\Component;
use Mary\Traits\Toast;
use App\Helpers\Cast;
use App\Models\Coa;
use App\Models\Balance;

new class extends Component {
    use Toast;

    #[Session(key: 'balance_year')]
    public $year = '';

    public $selected_coa = '';
    public $normal_balance = '';
    public $dc = false;
    public $amount = 0;

    public $sumDebit = 0;
    public $sumCredit = 0;
    public $validityStatus = false;
    public $validityMessage = '';
    public $drawer = false;
    public $selected = '';

    public function mount(): void
    {
        Gate::authorize('view opening-balance');

        $this->year = settings('opening_balance_period');

        $this->init();
        $this->validity();
    }

    public function balance()
    {
        return Balance::query()
        ->orderBy('coa_code')
        ->with('coa')
        ->filterWhere('year', $this->year)
        ->get();
    }

    public function with(): array
    {
        return [
            'balances' => $this->balance(),
        ];
    }

    public function btnUpdate($id)
    {
        $this->selected = $id;
        $this->update();
    }

    public function update()
    {
        $balance = Balance::find($this->selected);

        if (isset($balance->id))
        {
            $coa = Coa::firstWhere('code', $balance->coa_code ?? '');

            $this->selected_coa = $coa->code . ', ' . $coa->name;
            $this->normal_balance = $coa->normal_balance;
            $this->dc = $coa->normal_balance == 'D' ? false : true;
            $this->amount = abs($balance->amount);
            $this->drawer = true;
        }
        else
        {
            $this->error('Error','No selected row.');
        }
    }

    public function save(): void
    {
        $data = $this->validate([
            'amount' => ['required','numeric:0,2'],
        ]);

        $amount = Cast::number($this->amount);

        if ($this->dc) {
            $debit = 0;
            $credit = $amount;
        } else {
            $debit = $amount;
            $credit = 0;
        }

        Balance::find($this->selected)->update([
            'dc' => $this->dc ? 'C' : 'D',
            'debit' => $debit,
            'credit' => $credit,
        ]);

        $this->drawer = false;
        $this->validity();
        $this->success('Balance successfully updated.');
    }

    public function init(): void
    {
        $coas = Coa::query()->isActive()->get();

        foreach ($coas as $coa )
        {
            if ( Balance::where('coa_code', $coa->code)->where('year', $this->year)->doesntExist() )
            {
                Balance::create([
                    'year' => $this->year,
                    'coa_code' => $coa->code,
                    'dc' => 'D',
                    'debit' => 0,
                    'credit' => 0,
                    'amount' => 0
                ]);
            }
        }
    }

    public function validity(): void
    {
        $this->sumDebit = Balance::where('year', $this->year)->sum('debit');
        $this->sumCredit = Balance::where('year', $this->year)->sum('credit');

        $this->validityStatus = true;
        $this->validityMessage = '';

        if ( bccomp($this->sumDebit,$this->sumCredit,2) ) {
            $this->validityStatus = false;
            $this->validityMessage = 'Debit and Credit must be same';
        }
    }
}; ?>

<div
    x-data="{
        selected : $wire.entangle('selected'),
        dc : $wire.entangle('dc'),
        dcLabel : '',
        init : function() {
            this.dcLabel = this.dc ? 'Credit' : 'Debit'
            // $watch('drawer', value => { mask() })
            $watch('dc', value => { this.dcLabel = this.dc ? 'Credit' : 'Debit' })
        },
        select : function ($id) {
            if (this.selected == $id) {
                this.selected = '';
            } else {
                this.selected = $id
            }
        },
    }"
>
    <x-header title="Opening Balance" separator>
        <x-slot:actions>
            {{-- <x-button label="Back" link="{{ route('brand.index') }}" icon="o-arrow-uturn-left" /> --}}
            <x-input label="" wire:model.live.debounce="year" readonly />
            <x-button label="Update" wire:click="update" spinner="update" />
        </x-slot:actions>
    </x-header>

    <x-card>
        <div class="overflow-x-auto">
            <table class="table">
            <thead>
            <tr>
                <th>Account Code</th>
                <th>Account Name</th>
                <th class="lg:w-[200px] text-right">Debit</th>
                <th class="lg:w-[200px] text-right">Credit</th>
                <th class="lg:w-[200px] text-center">Action</th>
            </tr>
            </thead>
            <tbody>
            @forelse ( $balances as $balance )
            <tr :class="selected=='{{ $balance->id }}' ? 'bg-amber-100 dark:bg-amber-950 hover:!bg-amber-200 dark:hover:!bg-amber-900' : ''" class="hover:bg-base-300" @click="select('{{ $balance->id }}')">
                <td>{{ $balance->coa_code }}</td>
                <td>{{ $balance->coa->name ?? '' }}</td>
                <td class="text-right">
                    {{ \App\Helpers\Cast::money($balance->debit) }}
                </td>
                <td class="text-right">
                    {{ \App\Helpers\Cast::money($balance->credit) }}
                </td>
                <td class="py-0.5 text-center">
                    <x-button label="edit" wire:click="btnUpdate('{{ $balance->id }}')" spinner="btnUpdate('{{ $balance->id }}')" class="btn-sm btn-primary btn-soft" />
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="4">No data found.</td>
            </tr>
            @endforelse
            <tr class="bg-gray-100">
                <td colspan="2" class="text-right font-semibold">Total</td>
                <td class="text-right font-semibold">{{ \App\Helpers\Cast::money($sumDebit) }}</td>
                <td class="text-right font-semibold">{{ \App\Helpers\Cast::money($sumCredit) }}</td>
                <td></td>
            </tr>
            <tr>
                <td colspan="2" class="text-right font-semibold">Validity Status</td>
                @if ($validityStatus)
                <td colspan="2" class="text-right font-semibold text-success">Valid</td>
                @else
                <td colspan="2" class="text-right font-semibold text-error">Not Valid</td>
                @endif
                <td></td>
            </tr>
            <tr>
                <td colspan="2" class="text-right font-semibold">&nbsp;</td>
                <td colspan="2" class="text-right text-error">{{ $validityMessage }}</td>
                <td></td>
            </tr>
            </tbody>
            </table>
        </div>
    </x-card>

    {{-- FORM --}}
    {{-- x-mask: dynamic="$money($input,'.','')" --}}
    <x-drawer wire:model="drawer" title="Update Balance" right separator with-close-button class="lg:w-1/3">
        <x-form wire:submit="save">
            <div class="space-y-4">
                <x-input label="Coa" wire:model="selected_coa" readonly />
                <x-input label="Normal Balance" wire:model="normal_balance" readonly />
                <x-toggle label="D/C" wire:model="dc">
                    <x-slot:label>
                        <span x-text="dcLabel"></span>
                    </x-slot:label>
                </x-toggle>
                <x-input label="Amount" wire:model="amount" x-mask:dynamic="$money($input,'.','')" />
            </div>
            <x-slot:actions>
                <x-button label="Save" icon="o-paper-airplane" type="submit" spinner="save" class="btn-primary" />
            </x-slot:actions>
        </x-form>
    </x-drawer>
</div>
