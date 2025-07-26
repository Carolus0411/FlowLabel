<?php

use Illuminate\Support\Collection;
use Livewire\Volt\Component;
use Mary\Traits\Toast;
use App\Helpers\Cast;
use App\Rules\Number;
use App\Rules\SalesInvoicePaidCheck;
use App\Models\SalesInvoice;
use App\Models\SalesSettlement;
use App\Models\SalesSettlementDetail;

new class extends Component {
    use Toast;

    public SalesSettlement $salesSettlement;
    public $selected;

    public string $mode = '';
    public bool $drawer = false;
    public bool $open = true;

    public $sales_invoice_code = '';
    public $invoice_total_amount = '';
    public $invoice_balance_amount = 0;
    public $currency_id = '';
    public $currency_rate = 1;
    public $foreign_amount = 0;
    public $amount = 0;

    public function mount( $id = '' ): void
    {
        $this->salesSettlement = SalesSettlement::find($id);
    }

    public function with(): array
    {
        $this->open = $this->salesSettlement->status == 'open';

        return [
            'details' => $this->salesSettlement->details()->with(['salesInvoice','currency'])->get()
        ];
    }

    public function clearForm(): void
    {
        $this->selected = null;
        $this->sales_invoice_code = '';
        $this->currency_id = '';
        $this->currency_rate = 1;
        $this->invoice_total_amount = 0;
        $this->invoice_balance_amount = 0;
        $this->foreign_amount = 0;
        $this->amount = 0;
        $this->resetValidation();
    }

    public function add(): void
    {
        $this->clearForm();
        $this->mode = 'add';
        $this->drawer = true;
    }

    public function edit(SalesSettlementDetail $detail): void
    {
        $this->clearForm();
        $this->fill($detail);
        $this->getInvoice($detail->sales_invoice_code);

        $this->selected = $detail;
        $this->mode = 'edit';
        $this->drawer = true;
    }

    public function save(): void
    {
        $data = $this->validate([
            'sales_invoice_code' => 'required',
            'currency_id' => 'required',
            'currency_rate' => ['required', new Number],
            'foreign_amount' => ['required', new Number, new SalesInvoicePaidCheck($this->sales_invoice_code)],
        ]);

        $currency_rate = Cast::number($this->currency_rate);
        $foreign_amount = Cast::number($this->foreign_amount);
        $amount = $foreign_amount * $currency_rate;

        if ($this->mode == 'add')
        {
            $this->salesSettlement->details()->create([
                'sales_invoice_code' => $this->sales_invoice_code,
                'currency_id' => $this->currency_id,
                'currency_rate' => $currency_rate,
                'foreign_amount' => $foreign_amount,
                'amount' => $amount,
            ]);
        }

        if ($this->mode == 'edit')
        {
            $this->selected->update([
                'sales_invoice_code' => $this->sales_invoice_code,
                'currency_id' => $this->currency_id,
                'currency_rate' => $currency_rate,
                'foreign_amount' => $foreign_amount,
                'amount' => $amount,
            ]);
        }

        $data = $this->calculate();

        $this->drawer = false;
        $this->success('Detail has been updated.');
    }

    public function calculate(): void
    {
        $this->dispatch('detail-updated');
    }

    public function delete(string $id): void
    {
        SalesSettlementDetail::find($id)->delete();

        $this->calculate();

        $this->success('Detail has been deleted.');
    }

    public function updated($property, $value): void
    {
        if ($property == 'sales_invoice_code') {
            $this->getInvoice($value);
        }
    }

    public function getInvoice($code): void
    {
        $invoice = SalesInvoice::where('code', $code)->first();
        $this->invoice_total_amount = Cast::money($invoice->invoice_amount ?? 0);
        $this->invoice_balance_amount = Cast::money($invoice->balance_amount ?? 0);
    }
}; ?>

<div
    x-data="{ drawer : $wire.entangle('drawer') }"
    x-init="$watch('drawer', value => { mask() })"
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
                <th class="text-left">Invoice Code</th>
                <th class="text-right lg:w-[6rem]">Invoice Total</th>
                <th class="text-right lg:w-[6rem]">Invoice Balance</th>
                <th class="text-right lg:w-[3rem]">Currency</th>
                <th class="text-right lg:w-[6rem]">Rate</th>
                <th class="text-right lg:w-[9rem]">Paid Amount</th>
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
                <td wire:click="edit('{{ $detail->id }}')" class="">{{ $detail->salesInvoice->code ?? '' }}</td>
                <td wire:click="edit('{{ $detail->id }}')" class="text-right">{{ Cast::money($detail->salesInvoice->invoice_amount, 2) }}</td>
                <td wire:click="edit('{{ $detail->id }}')" class="text-right">{{ Cast::money($detail->invoice_balance_amount, 2) }}</td>
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
                <td class="">{{ $detail->salesInvoice->code ?? '' }}</td>
                <td class="text-right">{{ Cast::money($detail->salesInvoice->invoice_amount, 2) }}</td>
                <td class="text-right">{{ Cast::money($detail->invoice_balance_amount, 2) }}</td>
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
    {{-- x-mask: dynamic="$money($input,'.','')" --}}
    <x-drawer wire:model="drawer" title="Create Detail" right separator with-close-button class="lg:w-1/3">
        <x-form wire:submit="save">
            <div class="space-y-4">
                <x-choices-offline
                    label="Invoice"
                    :options="\App\Models\SalesInvoice::query()->get()"
                    wire:model.live="sales_invoice_code"
                    option-label="code"
                    option-value="code"
                    single
                    searchable
                    placeholder="-- Select --"
                />
                <div class="space-y-4 lg:space-y-0 lg:grid grid-cols-2 gap-4">
                    <x-input label="Invoice Total" wire:model="invoice_total_amount" class="money" disabled />
                    <x-input label="Invoice Balance" wire:model="invoice_balance_amount" class="money" disabled />
                    <x-choices-offline
                        label="Currency"
                        :options="\App\Models\Currency::query()->isActive()->get()"
                        wire:model="currency_id"
                        option-label="code"
                        single
                        searchable
                        placeholder="-- Select --"
                    />
                    <x-input label="Paid Amount" wire:model="foreign_amount" class="money" />
                    <x-input label="Rate" wire:model="currency_rate" class="money" />
                </div>
            </div>
            <x-slot:actions>
                <x-button label="Save" icon="o-paper-airplane" type="submit" spinner="save" class="btn-primary" />
            </x-slot:actions>
        </x-form>
    </x-drawer>
</div>
