<?php

use Illuminate\Support\Collection;
use Livewire\Volt\Component;
use Mary\Traits\Toast;
use App\Helpers\Cast;
use App\Rules\Number;
use App\Models\Coa;
use App\Models\OtherPayableSettlement;
use App\Models\OtherPayableSettlementDetail;
use App\Models\OtherPayableInvoice;

new class extends Component {
    use Toast;

    public OtherPayableSettlement $otherPayableSettlement;
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
    public $other_payable_invoice_code = '';
    public $invoice_total_amount = 0;
    public $invoice_balance_amount = 0;
    public $supplier_name = '';
    public Collection $otherPayableInvoice;

    public function mount( $id = '' ): void
    {
        $this->otherPayableSettlement = OtherPayableSettlement::find($id);
        $this->open = $this->otherPayableSettlement->status == 'open';
        $this->searchOtherPayableInvoice();
    }

    public function with(): array
    {
        return [
            'details' => $this->otherPayableSettlement->details()->with(['coa','currency','otherPayableInvoice.supplier'])->get()
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
        $this->other_payable_invoice_code = '';
        $this->invoice_total_amount = 0;
        $this->invoice_balance_amount = 0;
        $this->supplier_name = '';
        $this->resetValidation();
    }

    public function add(): void
    {
        $this->clearForm();
        $this->mode = 'add';
        $this->drawer = true;
    }

    public function edit(OtherPayableSettlementDetail $detail): void
    {
        $this->clearForm();

        $this->fill($detail);

        $this->selected = $detail;
        $this->getInvoice($detail->other_payable_invoice_code ?? '');

        $this->mode = 'edit';
        $this->drawer = true;
    }

    public function save(): void
    {
        $data = $this->validate([
            'other_payable_invoice_code' => 'required',
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
            $this->otherPayableSettlement->details()->create([
                'other_payable_settlement_code' => $this->otherPayableSettlement->code,
                'other_payable_invoice_code' => $this->other_payable_invoice_code,
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
                'other_payable_settlement_code' => $this->otherPayableSettlement->code,
                'other_payable_invoice_code' => $this->other_payable_invoice_code,
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
        $total_amount = $this->otherPayableSettlement->details()->sum('amount');

        $data = [
            'total_amount' => $total_amount,
        ];

        $this->dispatch('detail-updated', data: $data);
    }

    public function delete(string $id): void
    {
        OtherPayableSettlementDetail::find($id)->delete();

        $this->calculate();

        $this->success('Item has been deleted.');
    }

    public function updated($property, $value): void
    {
        if ($property == 'other_payable_invoice_code') {
            $this->getInvoice($value);
        }
        if (in_array($property, ['currency_rate', 'foreign_amount'])) {
            $currency_rate = Cast::number($this->currency_rate);
            $foreign_amount = Cast::number($this->foreign_amount);
            $this->amount = $currency_rate * $foreign_amount;
        }
    }

    public function searchOtherPayableInvoice(string $value = ''): void
    {
        $selected = OtherPayableInvoice::where('code', $this->other_payable_invoice_code)->get();

        $existingInvoiceCodes = $this->otherPayableSettlement->details()->pluck('other_payable_invoice_code')->toArray();

        $this->otherPayableInvoice = OtherPayableInvoice::query()
            ->closed()
            ->where('balance_amount', '>', '0')
            ->whereNotIn('code', $existingInvoiceCodes)
            ->filterLike('code', $value)
            ->take(20)
            ->get()
            ->merge($selected);
    }

    public function getInvoice($code): void
    {
        $invoice = OtherPayableInvoice::with(['supplier','details.serviceCharge.coaBuying','details.currency'])->where('code', $code)->first();
        if (!$invoice) {
            $this->invoice_total_amount = 0;
            $this->invoice_balance_amount = 0;
            $this->supplier_name = '';
            return;
        }

        $this->supplier_name = $invoice->supplier->name ?? '';
        $this->invoice_total_amount = Cast::money($invoice->invoice_amount ?? 0);
        $this->invoice_balance_amount = Cast::money($invoice->balance_amount ?? 0);

        // Select default first detail for currency
        $firstDetail = $invoice->details->first();
        if ($firstDetail) {
            $this->currency_id = $firstDetail->currency_id ?? '';
            $this->currency_rate = $firstDetail->currency_rate ?? 1;
        }

        // Default COA to Accrued Expenses (205-002)
        $this->coa_code = settings('accrued_expenses_code') ?: '205-002';

        // Accrued expenses amount = invoice_amount - pph_amount
        $accrued = ($invoice->invoice_amount ?? 0) - ($invoice->pph_amount ?? 0);
        $this->foreign_amount = Cast::money($accrued / max(1, Cast::number($this->currency_rate)));
        $this->amount = $accrued;
        $this->note = 'Settlement from invoice ' . $invoice->code;
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
                <th class="text-left">Invoice</th>
                <th class="text-left">Supplier</th>
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
                <td wire:click="edit('{{ $detail->id }}')" class="">{{ $detail->otherPayableInvoice->code ?? '' }}</td>
                <td wire:click="edit('{{ $detail->id }}')" class="">{{ $detail->otherPayableInvoice->supplier->name ?? '' }}</td>
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
                <td class="">{{ $detail->otherPayableInvoice->code ?? '' }}</td>
                <td class="">{{ $detail->otherPayableInvoice->supplier->name ?? '' }}</td>
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
                <x-choices
                    label="Invoice"
                    wire:model.live="other_payable_invoice_code"
                    :options="$otherPayableInvoice"
                    search-function="searchOtherPayableInvoice"
                    option-label="code"
                    option-sub-label="invoice_amount"
                    option-value="code"
                    single
                    searchable
                    clearable
                    placeholder="-- Select --"
                />
                <x-input label="Supplier" wire:model="supplier_name" disabled />
                <div class="space-y-4 lg:space-y-0 lg:grid grid-cols-2 gap-4">
                    <x-input label="Invoice Total" wire:model="invoice_total_amount" class="money" disabled />
                    <x-input label="Invoice Balance" wire:model="invoice_balance_amount" class="money" disabled />
                </div>
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
                <div class="space-y-4 lg:space-y-0 lg:grid grid-cols-2 gap-4">
                    <x-choices-offline
                        label="Currency"
                        :options="\App\Models\Currency::query()->isActive()->get()"
                        wire:model="currency_id"
                        option-label="code"
                        single
                        searchable
                        placeholder="-- Select --"
                    />
                    <x-input label="Rate" wire:model.live="currency_rate" class="money" />
                </div>
                <div class="space-y-4 lg:space-y-0 lg:grid grid-cols-2 gap-4">
                    <x-input label="Amount" wire:model.live="foreign_amount" class="money" />
                    <x-input label="IDR Amount" wire:model="amount" readonly x-mask:dynamic="$money($input,'.',',')" />
                </div>
                <x-input label="Note" wire:model="note" />
            </div>
            <x-slot:actions>
                <x-button label="Save" icon="o-paper-airplane" type="submit" spinner="save" class="btn-primary" />
            </x-slot:actions>
        </x-form>
    </x-drawer>
</div>
