<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Livewire\Attributes\Reactive;
use Livewire\Volt\Component;
use Livewire\Attributes\On;
use Mary\Traits\Toast;
use App\Helpers\Cast;
use App\Rules\Number;
use App\Rules\PurchaseInvoicePaidCheck;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseSettlement;
use App\Models\PurchaseSettlementDetail;

new class extends Component {
    use Toast;

    public PurchaseSettlement $purchaseSettlement;
    public $supplier_id;
    public $selected;

    public string $mode = '';
    public bool $drawer = false;
    public bool $open = true;

    public $purchase_invoice_code = '';
    public $invoice_total_amount = '';
    public $invoice_balance_amount = 0;
    public $currency_id = '';
    public $currency_rate = 1;
    public $foreign_amount = 0;
    public $amount = 0;

    public Collection $purchaseInvoice;

    #[On('supplier-changed')]
    public function supplierChanged($value)
    {
        $this->supplier_id = $value;
    }

    public function searchPurchaseInvoice(string $value = ''): void
    {
        $selected = PurchaseInvoice::where('code', $this->purchase_invoice_code)->get();

        // Get existing invoice codes in current settlement details
        $existingInvoiceCodes = $this->purchaseSettlement->details()->pluck('purchase_invoice_code')->toArray();

        $this->purchaseInvoice = PurchaseInvoice::query()
            ->closed()
            ->where('supplier_id', $this->supplier_id)
            ->where('balance_amount', '>', '0')
            ->whereNotIn('code', $existingInvoiceCodes)
            ->filterLike('code', $value)
            ->take(20)
            ->get()
            ->merge($selected);
    }

    public function mount( $id = '' ): void
    {
        $this->purchaseSettlement = PurchaseSettlement::find($id);
        $this->searchPurchaseInvoice();
    }

    public function with(): array
    {
        $this->open = $this->purchaseSettlement->status == 'open';

        return [
            'details' => $this->purchaseSettlement->details()->with(['purchaseInvoice','currency'])->get()
        ];
    }

    public function clearForm(): void
    {
        $this->selected = null;
        $this->purchase_invoice_code = '';
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
        $this->searchPurchaseInvoice();
        $this->mode = 'add';
        $this->drawer = true;
    }

    public function edit(PurchaseSettlementDetail $detail): void
    {
        $this->clearForm();
        $this->fill($detail);
        $this->getInvoice($detail->purchase_invoice_code);

        $this->selected = $detail;
        $this->mode = 'edit';
        $this->drawer = true;
    }

    public function save(): void
    {
        $data = $this->validate([
            'purchase_invoice_code' => 'required',
            'currency_id' => 'required',
            'currency_rate' => ['required', new Number],
            'foreign_amount' => ['required', new Number, new PurchaseInvoicePaidCheck($this->purchase_invoice_code)],
        ]);

        $currency_rate = Cast::number($this->currency_rate);
        $foreign_amount = Cast::number($this->foreign_amount);
        $amount = $foreign_amount * $currency_rate;

        if ($this->mode == 'add')
        {
            $detail = $this->purchaseSettlement->details()->create([
                'purchase_invoice_code' => $this->purchase_invoice_code,
                'currency_id' => $this->currency_id,
                'currency_rate' => $currency_rate,
                'foreign_amount' => $foreign_amount,
                'amount' => $amount,
            ]);
            // Note: balance_amount will be updated when Settlement is approved (close)
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
        $detail = PurchaseSettlementDetail::find($id);
        // Note: balance_amount restore will be handled when Settlement is deleted (if already approved)
        $detail->delete();

        $this->calculate();

        $this->success('Detail has been deleted.');
    }

    public function updated($property, $value): void
    {
        if ($property == 'purchase_invoice_code') {
            $this->getInvoice($value);
        }
    }

    public function getInvoice($code): void
    {
        $invoice = PurchaseInvoice::where('code', $code)->first();
        $this->invoice_total_amount = Cast::number($invoice->invoice_amount ?? 0);
        $this->invoice_balance_amount = Cast::number($invoice->balance_amount ?? 0);
        if (empty($this->foreign_amount)) {
            $this->foreign_amount = $this->invoice_balance_amount;
        }
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
                <th class="text-right lg:w-24">Invoice Total</th>
                <th class="text-right lg:w-24">Invoice Balance</th>
                <th class="text-right lg:w-12">Currency</th>
                <th class="text-right lg:w-24">Rate</th>
                <th class="text-right lg:w-36">Paid Amount</th>
                <th class="text-right lg:w-36">IDR Amount</th>
                @if ($open)
                <th class="lg:w-16"></th>
                @endif
            </tr>
            </thead>
            <tbody>

            @forelse ($details as $key => $detail)
            @if ($open)
            <tr wire:key="table-row-{{ $detail->id }}" wire:loading.class="cursor-wait" class="divide-x divide-gray-200 dark:divide-gray-900 hover:bg-yellow-50 dark:hover:bg-gray-800">
                {{-- wire : click="edit('{{ $detail->id }}')" --}}
                <td class="">{{ $detail->purchaseInvoice->code ?? '' }}</td>
                <td class="text-right lg:w-24">{{ Cast::money($detail->purchaseInvoice->invoice_amount, 2) }}</td>
                <td class="text-right lg:w-24">{{ Cast::money($detail->purchaseInvoice->balance_amount, 2) }}</td>
                <td class="">{{ $detail->currency->code ?? '' }}</td>
                <td class="text-right">{{ Cast::money($detail->currency_rate, 2) }}</td>
                <td class="text-right">{{ Cast::money($detail->foreign_amount, 2) }}</td>
                <td class="text-right">{{ Cast::money($detail->amount, 2) }}</td>
                <td>
                <div class="flex items-center">
                    <x-button icon="o-x-mark" wire:click="delete('{{ $detail->id }}')" spinner="delete('{{ $detail->id }}')" wire:confirm="Are you sure ?" class="btn-xs btn-ghost text-xs -m-1 text-error" />
                </div>
                </td>
            </tr>
            @else
            <tr wire:key="table-row-{{ $detail->id }}" class="divide-x divide-gray-200 dark:divide-gray-900 hover:bg-yellow-50 dark:hover:bg-gray-800">
                <td class="">{{ $detail->purchaseInvoice->code ?? '' }}</td>
                <td class="text-right lg:w-24">{{ Cast::money($detail->purchaseInvoice->invoice_amount, 2) }}</td>
                <td class="text-right lg:w-24">{{ Cast::money($detail->purchaseInvoice->balance_amount, 2) }}</td>
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
                {{-- <x-choices-offline
                    label="Invoice"
                    : options="\App\Models\SalesInvoice::query()->get()"
                    wire : model.live="sales_invoice_code"
                    option-label="code"
                    option-value="code"
                    single
                    searchable
                    placeholder="-- Select --"
                /> --}}
                <x-choices
                    label="Invoice"
                    wire:model.live="purchase_invoice_code"
                    :options="$purchaseInvoice"
                    search-function="searchPurchaseInvoice"
                    option-label="code"
                    option-sub-label="invoice_amount"
                    option-value="code"
                    single
                    searchable
                    clearable
                    placeholder="-- Select --"
                    :disabled="!$open"
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
