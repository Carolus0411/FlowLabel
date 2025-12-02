<?php

use Illuminate\Support\Collection;
use Livewire\Volt\Component;
use Mary\Traits\Toast;
use App\Helpers\Cast;
use App\Rules\Number;
use App\Models\Coa;
use App\Models\BankIn;
use App\Models\BankInDetail;

new class extends Component {
    use Toast;

    public BankIn $bankIn;
    public $selected;

    public $mode = '';
    public bool $drawer = false;
    public bool $open = true;

    public $coa_code = '';
    public $currency_id = '';
    public $currency_rate = 0;
    public $foreign_amount = 0;
    public $amount = 0;
    public $note = '';

    public function mount( $id = '' ): void
    {
        $this->bankIn = BankIn::find($id);
        $this->open = $this->bankIn->status == 'open';
    }

    public function with(): array
    {
        return [
            'details' => $this->bankIn->details()->with(['coa','currency'])->get()
        ];
    }

    public function clearForm(): void
    {
        $this->selected = null;
        $this->coa_code = '';
        $this->currency_id = '';
        $this->currency_rate = '';
        $this->foreign_amount = '';
        $this->amount = 0;
        $this->note = '';
        $this->resetValidation();
    }

    public function add(): void
    {
        $this->clearForm();
        $this->mode = 'add';
        $this->drawer = true;
    }

    public function edit(BankInDetail $detail): void
    {
        $this->clearForm();

        $this->fill($detail);

        $this->selected = $detail;

        $this->mode = 'edit';
        $this->drawer = true;
    }

    public function save(): void
    {
        $data = $this->validate([
            'coa_code' => 'required',
            'currency_id' => 'required',
            'currency_rate' => ['required', new Number],
            'foreign_amount' => ['required', new Number],
            'note' => 'required',
        ]);

        $currency_rate = Cast::number($this->currency_rate);
        $foreign_amount = Cast::number($this->foreign_amount);
        $amount = $foreign_amount * $currency_rate;

        if ($this->mode == 'add')
        {
            $this->bankIn->details()->create([
                'coa_code' => $this->coa_code,
                'currency_id' => $this->currency_id,
                'currency_rate' => $currency_rate,
                'foreign_amount' => $foreign_amount,
                'amount' => $amount,
                'note' => $this->note,
            ]);
        }

        if ($this->mode == 'edit')
        {
            $this->selected->update([
                'coa_code' => $this->coa_code,
                'currency_id' => $this->currency_id,
                'currency_rate' => $currency_rate,
                'foreign_amount' => $foreign_amount,
                'amount' => $amount,
                'note' => $this->note,
            ]);
        }

        $this->calculate();

        $this->drawer = false;
        $this->success('Item has been created.');
    }

    public function calculate(): void
    {
        $total_amount = $this->bankIn->details()->sum('amount');

        $data = [
            'total_amount' => $total_amount,
        ];

        $this->dispatch('detail-updated', data: $data);
    }

    public function delete(string $id): void
    {
        BankInDetail::find($id)->delete();

        $this->calculate();

        $this->success('Item has been deleted.');
    }
}; ?>

<div
    x-data="{
        drawer : $wire.entangle('drawer'),
        init : function() {
            $watch('drawer', value => { mask() })
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
                <th class="text-right lg:w-[3rem]">Currency</th>
                <th class="text-right lg:w-[6rem]">Rate</th>
                <th class="text-right lg:w-[9rem]">FG Amount</th>
                <th class="text-right lg:w-[9rem]">IDR Amount</th>
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
                <td wire:click="edit('{{ $detail->id }}')" class="">{{ $detail->note ?? '' }}</td>
                <td wire:click="edit('{{ $detail->id }}')" class="">{{ $detail->currency->code ?? '' }}</td>
                <td wire:click="edit('{{ $detail->id }}')" class="text-right">{{ Cast::money($detail->currency_rate, 2) }}</td>
                <td wire:click="edit('{{ $detail->id }}')" class="text-right">{{ Cast::money($detail->foreign_amount, 2) }}</td>
                <td wire:click="edit('{{ $detail->id }}')" class="text-right">{{ Cast::money($detail->amount, 2) }}</td>
                <td>
                <div class="flex items-center">
                    <x-button icon="o-x-mark" wire:click="delete('{{ $detail->id }}')" spinner="delete('{{ $detail->id }}')" wire:confirm="Are you sure ?" class="btn-xs btn-ghost text-xs -m-1 text-error" />
                </div>
                </td>
            </tr>
            @else
            <tr wire:key="table-row-{{ $detail->id }}" class="divide-x divide-gray-200 dark:divide-gray-900 hover:bg-yellow-50 dark:hover:bg-gray-800">
                <td class=""><b>{{ $detail->coa->code ?? '' }}</b>, {{ $detail->coa->name ?? '' }}</td>
                <td class="">{{ $detail->note }}</td>
                <td class="">{{ $detail->currency->code ?? '' }}</td>
                <td class="text-right">{{ Cast::money($detail->currency_rate, 2) }}</td>
                <td class="text-right">{{ Cast::money($detail->foreign_amount, 2) }}</td>
                <td class="text-right">{{ Cast::money($detail->amount, 2) }}</td>
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
                <x-choices-offline
                    label="Currency"
                    :options="\App\Models\Currency::query()->isActive()->get()"
                    wire:model="currency_id"
                    option-label="code"
                    single
                    searchable
                    placeholder="-- Select --"
                />
                <x-input label="Amount" wire:model="foreign_amount" class="money" />
                <x-input label="Rate" wire:model="currency_rate" class="money" />
                <x-input label="Note" wire:model="note" />
            </div>
            <x-slot:actions>
                <x-button label="Save" icon="o-paper-airplane" type="submit" spinner="save" class="btn-primary" />
            </x-slot:actions>
        </x-form>
    </x-drawer>
</div>
