<?php

use Illuminate\Support\Collection;
use Livewire\Volt\Component;
use Mary\Traits\Toast;
use App\Helpers\Cast;
use App\Rules\Number;
use App\Models\Coa;
use App\Models\Journal;
use App\Models\JournalDetail;

new class extends Component {
    use Toast;

    public Journal $journal;
    public $selected;

    public string $mode = '';
    public bool $drawer = false;
    public bool $open = true;

    public $coa_code = '';
    public $description = '';
    public bool $dc = false;
    public $amount = 0;

    public function mount( $id = '' ): void
    {
        $this->journal = Journal::find($id);
        $this->open = $this->journal->status == 'open';
    }

    public function with(): array
    {
        return [
            'details' => $this->journal->details()->with(['coa'])->get()
        ];
    }

    public function clearForm(): void
    {
        $this->selected = null;
        $this->coa_code = '';
        $this->description = '';
        $this->dc = false;
        $this->amount = 0;
        $this->resetValidation();
    }

    public function add(): void
    {
        $this->clearForm();
        $this->mode = 'add';
        $this->drawer = true;
    }

    public function edit(JournalDetail $detail): void
    {
        $this->clearForm();

        $detail->dc = $detail->dc == 'C' ? true : false;
        $detail->amount = abs($detail->amount);

        $this->fill($detail);

        $this->selected = $detail;

        $this->mode = 'edit';
        $this->drawer = true;
    }

    public function save(): void
    {
        $data = $this->validate([
            'coa_code' => 'required',
            'description' => 'required',
            'dc' => 'required',
            'amount' => ['required', new Number],
        ]);

        $amount = Cast::number($this->amount);

        if ($this->dc) {
            $debit = 0;
            $credit = $amount;
        } else {
            $debit = $amount;
            $credit = 0;
        }

        if ($this->mode == 'add')
        {
            $this->journal->details()->create([
                'coa_code' => $this->coa_code,
                'description' => $this->description,
                'dc' => $this->dc ? 'C' : 'D',
                'debit' => $debit,
                'credit' => $credit,
                'date' => $this->journal->date,
            ]);
        }

        if ($this->mode == 'edit')
        {
            $this->selected->update([
                'coa_code' => $this->coa_code,
                'description' => $this->description,
                'dc' => $this->dc ? 'C' : 'D',
                'debit' => $debit,
                'credit' => $credit,
                'date' => $this->journal->date,
            ]);
        }

        $this->calculate();

        $this->drawer = false;
        $this->success('Item has been created.');
    }

    public function calculate(): void
    {
        $debit_total = $this->journal->details()->sum('debit');
        $credit_total = $this->journal->details()->sum('debit');

        $data = [
            'debit_total' => $debit_total,
            'credit_total' => $credit_total,
        ];

        $this->dispatch('detail-updated', data: $data);
    }

    public function delete(string $id): void
    {
        JournalDetail::find($id)->delete();

        $this->calculate();

        $this->success('Item has been deleted.');
    }

    public function updated($property, $value): void
    {
        // updated here ...
    }
}; ?>

<div
    x-data="{
        drawer : $wire.entangle('drawer'),
        dc : $wire.entangle('dc'),
        dcLabel : '',
        init : function() {
            this.dcLabel = this.dc ? 'Credit' : 'Debit'
            $watch('drawer', value => { mask() })
            $watch('dc', value => { this.dcLabel = this.dc ? 'Credit' : 'Debit' })
        }
    }"
>
    <x-card title="Details" separator progress-indicator>
        <x-slot:menu>
            @if ($open)
            <x-button label="Add Detail" icon="o-plus" wire:click="add" spinner="add" class="" />
            @endif
        </x-slot:menu>

        <div class="overflow-x-auto">
            <table class="table">
            <thead>
            <tr>
                <th class="text-left">Account</th>
                <th class="text-left">Description</th>
                <th class="text-right lg:w-[9rem]">Debit</th>
                <th class="text-right lg:w-[9rem]">Credit</th>
                @if ($open)
                <th class="lg:w-[4rem]"></th>
                @endif
            </tr>
            </thead>
            <tbody>

            @forelse ($details as $key => $detail)
            @if ($open)
            <tr wire:key="table-row-{{ $detail->id }}" wire:loading.class="cursor-wait" class="divide-x divide-gray-200 dark:divide-gray-900 hover:bg-yellow-50 dark:hover:bg-gray-800 cursor-pointer">
                <td wire:click="edit('{{ $detail->id }}')" class=""><b>{{ $detail->coa->code ?? '' }}</b>, {{ $detail->coa->name ?? '' }}</td>
                <td wire:click="edit('{{ $detail->id }}')" class="">{{ $detail->description ?? '' }}</td>
                <td wire:click="edit('{{ $detail->id }}')" class="text-right">{{ Cast::money($detail->debit, 2) }}</td>
                <td wire:click="edit('{{ $detail->id }}')" class="text-right">{{ Cast::money($detail->credit, 2) }}</td>
                <td>
                <div class="flex items-center">
                    <x-button icon="o-x-mark" wire:click="delete('{{ $detail->id }}')" spinner="delete('{{ $detail->id }}')" wire:confirm="Are you sure ?" class="btn-xs btn-ghost text-xs -m-1 text-error" />
                </div>
                </td>
            </tr>
            @else
            <tr wire:key="table-row-{{ $detail->id }}" class="divide-x divide-gray-200 dark:divide-gray-900 hover:bg-yellow-50 dark:hover:bg-gray-800">
                <td class=""><b>{{ $detail->coa->code ?? '' }}</b>, {{ $detail->coa->name ?? '' }}</td>
                <td class="">{{ $detail->description }}</td>
                <td class="text-right">{{ Cast::money($detail->debit, 2) }}</td>
                <td class="text-right">{{ Cast::money($detail->credit, 2) }}</td>
            </tr>
            @endif
            @empty
            <tr class="divide-x divide-gray-200 dark:divide-gray-900 hover:bg-yellow-50 dark:hover:bg-gray-800">
                <td colspan="10" class="text-center">No record found.</td>
            </tr>
            @endforelse

            </tbody>
            </table>
        </div>
    </x-card>

    {{-- FORM --}}
    {{-- x-mask: dynamic="$money($input,'.','')" --}}
    <x-drawer wire:model="drawer" title="Create Item" right separator with-close-button class="lg:w-1/3">
        <x-form wire:submit="save">
            <div class="space-y-4">
                <x-choices-offline
                    label="Coa"
                    :options="\App\Models\Coa::query()->isActive()->orderBy('code')->get()"
                    wire:model="coa_code"
                    option-value="code"
                    option-label="full_name"
                    single
                    searchable
                    placeholder="-- Select --"
                />
                <x-toggle label="D/C" wire:model="dc">
                    <x-slot:label>
                        <span x-text="dcLabel"></span>
                    </x-slot:label>
                </x-toggle>
                <x-input label="Amount" wire:model="amount" class="money" />
                <x-input label="Note" wire:model="description" />
            </div>
            <x-slot:actions>
                <x-button label="Save" icon="o-paper-airplane" type="submit" spinner="save" class="btn-primary" />
            </x-slot:actions>
        </x-form>
    </x-drawer>
</div>
