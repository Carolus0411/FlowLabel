<?php

use Illuminate\Support\Collection;
use Livewire\Volt\Component;
use Mary\Traits\Toast;
use App\Helpers\Cast;
use App\Rules\Number;
use App\Models\ServiceCharge;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceDetail;

new class extends Component {
    use Toast;

    public SalesInvoice $salesInvoice;
    public $selected;

    public string $mode = '';
    public bool $drawer = false;
    public bool $open = true;

    public $service_charge_id = '';
    public $note = '';
    public $uom_id = '';
    public $currency_id = '';
    public $currency_rate = 1;
    public $qty = 0;
    public $price = 0;
    public $foreign_amount = 0;
    public $amount = 0;

    public function mount( $id = '' ): void
    {
        $this->salesInvoice = SalesInvoice::find($id);
    }

    public function with(): array
    {
        return [
            'details' => $this->salesInvoice->details()->with(['serviceCharge','currency','uom'])->get()
        ];
    }

    public function clearForm(): void
    {
        $this->selected = null;
        $this->service_charge_id = '';
        $this->note = '';
        $this->uom_id = '';
        $this->currency_id = '';
        $this->currency_rate = 1;
        $this->qty = 0;
        $this->price = 0;
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

    public function edit(SalesInvoiceDetail $detail): void
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
            'service_charge_id' => 'required',
            'note' => 'required',
            'uom_id' => 'required',
            'currency_id' => 'required',
            'currency_rate' => ['required', new Number],
            'qty' => ['required', new Number],
            'price' => ['required', new Number],
        ]);

        $currency_rate = Cast::number($this->currency_rate);
        $qty = Cast::number($this->qty);
        $price = Cast::number($this->price);

        $foreign_amount = $qty * $price;
        $amount = $foreign_amount * $currency_rate;

        if ($this->mode == 'add')
        {
            $this->salesInvoice->details()->create([
                'service_charge_id' => $this->service_charge_id,
                'note' => $this->note,
                'uom_id' => $this->uom_id,
                'currency_id' => $this->currency_id,
                'currency_rate' => $currency_rate,
                'qty' => $qty,
                'price' => $price,
                'foreign_amount' => $foreign_amount,
                'amount' => $amount,
            ]);
        }

        if ($this->mode == 'edit')
        {
            $this->selected->update([
                'service_charge_id' => $this->service_charge_id,
                'note' => $this->note,
                'uom_id' => $this->uom_id,
                'currency_id' => $this->currency_id,
                'currency_rate' => $currency_rate,
                'qty' => $qty,
                'price' => $price,
                'foreign_amount' => $foreign_amount,
                'amount' => $amount,
            ]);
        }

        $data = $this->calculate();

        $this->drawer = false;
        $this->success('Item has been created.');
    }

    public function calculate(): void
    {
        $dpp_amount = $this->salesInvoice->details()->sum('amount');

        $data = [
            'dpp_amount' => $dpp_amount,
        ];

        $this->dispatch('detail-updated', data: $data);
    }

    public function delete(string $id): void
    {
        SalesInvoiceDetail::find($id)->delete();

        $this->calculate();

        $this->success('Item has been deleted.');
    }

    public function updated($property, $value): void
    {
        // if ($property == 'item_id') {
        //     $item = Item::find($value);
        //     $this->price = $item->selling_price ?? 0;
        // }
    }
}; ?>

<div>
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
                <th class="text-left">Service Charge</th>
                <th class="text-right lg:w-[4rem]">Qty</th>
                <th class="text-right lg:w-[4rem]">Unit</th>
                <th class="text-right lg:w-[6rem]">Price</th>
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
            <tr wire:key="table-row-{{ $detail->id }}" wire:loading.class="cursor-wait" class="divide-x divide-gray-200 dark:divide-gray-900 hover:bg-yellow-50 dark:hover:bg-gray-800 cursor-pointer">
                @if ($open)
                <td wire:click="edit('{{ $detail->id }}')" class=""><b>{{ $detail->serviceCharge->code ?? '' }}</b>, {{ $detail->serviceCharge->name ?? '' }}</td>
                <td wire:click="edit('{{ $detail->id }}')" class="text-right">{{ Cast::money($detail->qty, 2) }}</td>
                <td wire:click="edit('{{ $detail->id }}')" class="">{{ $detail->uom->code ?? '' }}</td>
                <td wire:click="edit('{{ $detail->id }}')" class="text-right">{{ Cast::money($detail->price, 2) }}</td>
                <td wire:click="edit('{{ $detail->id }}')" class="">{{ $detail->currency->code ?? '' }}</td>
                <td wire:click="edit('{{ $detail->id }}')" class="text-right">{{ Cast::money($detail->currency_rate, 2) }}</td>
                <td wire:click="edit('{{ $detail->id }}')" class="text-right">{{ Cast::money($detail->foreign_amount, 2) }}</td>
                <td wire:click="edit('{{ $detail->id }}')" class="text-right">{{ Cast::money($detail->amount, 2) }}</td>
                <td>
                <div class="flex items-center">
                    <x-button icon="o-x-mark" wire:click="delete('{{ $detail->id }}')" spinner="delete('{{ $detail->id }}')" wire:confirm="Are you sure ?" class="btn-xs btn-ghost text-xs -m-1 text-error" />
                </div>
                </td>
                @else
                <td class="">{{ $detail->serviceCharge->code ?? '' }}; {{ $detail->serviceCharge->name ?? '' }}</td>
                <td class="text-right">{{ Cast::money($detail->qty, 2) }}</td>
                <td class="">{{ $detail->uom->code ?? '' }}</td>
                <td class="text-right">{{ Cast::money($detail->price, 2) }}</td>
                <td class="">{{ $detail->currency->code ?? '' }}</td>
                <td class="text-right">{{ Cast::money($detail->currency_rate, 2) }}</td>
                <td class="text-right">{{ Cast::money($detail->foreign_amount, 2) }}</td>
                <td class="text-right">{{ Cast::money($detail->amount, 2) }}</td>
                @endif
            </tr>
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
                <x-choices-offline label="Service Charge" :options="\App\Models\ServiceCharge::query()->isActive()->get()" wire:model="service_charge_id" single searchable placeholder="-- Select --" />
                <div class="space-y-4 lg:space-y-0 lg:grid grid-cols-2 gap-4">
                    <x-choices-offline label="Unit" :options="\App\Models\Uom::query()->isActive()->get()" wire:model="uom_id" option-label="code" single searchable placeholder="-- Select --" />
                    <x-choices-offline label="Currency" :options="\App\Models\Currency::query()->isActive()->get()" wire:model="currency_id" option-label="code" single searchable placeholder="-- Select --" />
                    <x-input label="Qty" wire:model="qty" x-mask:dynamic="$money($input,'.','')" />
                    <x-input label="Price" wire:model="price" x-mask:dynamic="$money($input,'.','')" />
                    <x-input label="Rate" wire:model="currency_rate" x-mask:dynamic="$money($input,'.','')" />
                </div>
                <x-input label="Note" wire:model="note" />
            </div>
            <x-slot:actions>
                <x-button label="Save" icon="o-paper-airplane" type="submit" spinner="save" class="btn-primary" />
            </x-slot:actions>
        </x-form>
    </x-drawer>
</div>
