<?php

use Illuminate\Support\Collection;
use Livewire\Volt\Component;
use Livewire\Attributes\On;
use Livewire\Attributes\Reactive;
use Mary\Traits\Toast;
use App\Helpers\Cast;
use App\Rules\Number;
use App\Models\ServiceCharge;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceDetail;
use App\Models\DeliveryOrder;
use App\Models\Currency;

new class extends Component {
    use Toast;

    public SalesInvoice $salesInvoice;
    public $selected;

    public string $mode = '';
    public bool $drawer = false;
    public bool $open = true;
    public bool $deliveryOrderModal = false;

    public $service_charge_id = '';
    public $note = '';
    public $uom_id = '';
    public $currency_id = '';
    public $currency_rate = 1;
    public $qty = 0;
    public $price = 0;
    public $foreign_amount = 0;
    public $amount = 0;

    #[Reactive]
    public $contact_id = '';
    public Collection $serviceCharge;

    // Delivery Order selection
    public $selectedDeliveryOrder = null;
    public Collection $deliveryOrders;
    public string $deliveryOrderSearch = '';

    public function searchServiceCharge(string $value = ''): void
    {
        $selected = ServiceCharge::where('id', intval($this->service_charge_id))->get();
        $this->serviceCharge = ServiceCharge::query()
            ->where(function ($query) use ($value) {
                $query->filterLike('code', $value);
                $query->orFilterLike('name', $value);
            })
            ->isActive()
            ->take(20)
            ->get()
            ->merge($selected);
    }

    #[On('contact-changed')]
    public function contactChanged($value)
    {
        $this->contact_id = $value;
        // Refresh the delivery order list if modal is open
        if ($this->deliveryOrderModal) {
            $this->searchDeliveryOrder();
        }
    }

    public function updatedContactId($value): void
    {
        if ($this->deliveryOrderModal) {
            $this->searchDeliveryOrder();
        }
    }

    public function mount( $id = '', $contact_id = '' ): void
    {
        $this->salesInvoice = SalesInvoice::find($id);
        $this->contact_id = $contact_id;
        $this->searchServiceCharge();
        $this->deliveryOrders = collect();
        $this->deliveryOrderSearch = '';
    }

    public function searchDeliveryOrder(string $value = ''): void
    {
        $contactId = $this->contact_id;

        // Get IDs of delivery orders that are already attached to any sales invoice
        $usedDeliveryOrderIds = SalesInvoice::whereNotNull('delivery_order_id')
            ->pluck('delivery_order_id')
            ->toArray();

        $query = DeliveryOrder::stored()
            ->closed()
            ->whereNotIn('id', $usedDeliveryOrderIds);

        // Only show delivery orders if a contact is selected
        if ($contactId) {
            $query->where('contact_id', $contactId);
        } else {
            // If no contact selected, return empty collection
            $this->deliveryOrders = collect();
            return;
        }

        $this->deliveryOrders = $query
            ->when(!empty($value), fn($q) => $q->where('code', 'like', '%' . $value . '%'))
            ->with(['contact', 'details.serviceCharge', 'details.uom'])
            ->orderBy('delivery_date', 'desc')
            ->take(20)
            ->get();
    }

    public function updatedDeliveryOrderSearch($value): void
    {
        $this->searchDeliveryOrder($value);
    }

    public function openDeliveryOrderModal(): void
    {
        if (is_null($this->salesInvoice->id)) {
            $this->error('Please save the invoice first before selecting delivery order.');
            return;
        }

        $this->deliveryOrderSearch = '';
        $this->searchDeliveryOrder();
        $this->selectedDeliveryOrder = null;
        $this->deliveryOrderModal = true;
    }

    public function selectDeliveryOrder($deliveryOrderId): void
    {
        $this->selectedDeliveryOrder = DeliveryOrder::with(['contact', 'details.serviceCharge', 'details.uom'])->find($deliveryOrderId);
    }

    public function importFromDeliveryOrder(): void
    {
        if (!$this->selectedDeliveryOrder) {
            $this->error('Please select a delivery order first.');
            return;
        }

        // Reload the delivery order with all necessary relationships
        $deliveryOrder = DeliveryOrder::with(['contact', 'details.serviceCharge', 'details.uom'])->find($this->selectedDeliveryOrder->id);

        // Get default currency (IDR)
        $defaultCurrency = Currency::where('code', 'IDR')->first();
        $currencyId = $defaultCurrency->id ?? 1;

        foreach ($deliveryOrder->details as $detail) {
            $amount = $detail->qty * $detail->price;

            $this->salesInvoice->details()->create([
                'service_charge_id' => $detail->service_charge_id,
                'note' => $detail->note ?? $detail->serviceCharge->name ?? '',
                'uom_id' => $detail->uom_id,
                'currency_id' => $currencyId,
                'currency_rate' => 1,
                'qty' => $detail->qty,
                'price' => $detail->price,
                'foreign_amount' => $amount,
                'amount' => $amount,
            ]);
        }

        // Link the delivery order to the invoice
        $this->salesInvoice->update(['delivery_order_id' => $deliveryOrder->id]);

        $this->calculate();
        $this->deliveryOrderModal = false;
        $this->selectedDeliveryOrder = null;
        $this->success('Items from delivery order imported successfully.');
    }

    public function calculate(): void
    {
        if (is_null($this->salesInvoice->id)) {
            return;
        }

        $dpp_amount = $this->salesInvoice->details()->sum('amount');

        $data = [
            'dpp_amount' => $dpp_amount,
        ];

        $this->dispatch('detail-updated', data: $data);
    }

    public function with(): array
    {
        $this->open = $this->salesInvoice->status == 'open';

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
        $this->searchServiceCharge();
        $this->mode = 'add';
        $this->drawer = true;
    }

    public function edit(SalesInvoiceDetail $detail): void
    {
        $this->clearForm();

        $this->fill($detail);
        $this->searchServiceCharge();

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

<div
    x-data="{ drawer : $wire.entangle('drawer') }"
    x-init="$watch('drawer', value => { mask() })"
>
    <x-card title="Details" separator progress-indicator>
        <x-slot:menu>
            @if ($open)
            <x-button label="Select Delivery Order" icon="o-clipboard-document-check" wire:click="openDeliveryOrderModal" spinner="openDeliveryOrderModal" class="btn-soft" />
            <x-button label="Add Detail" icon="o-plus" wire:click="add" spinner="add" class="" />
            @endif
        </x-slot:menu>

        <div class="overflow-x-auto">
            <table class="table">
            <thead>
            <tr>
                <th class="text-left">Items Master</th>
                <th class="text-right lg:w-16">Qty</th>
                <th class="text-right lg:w-16">Unit</th>
                <th class="text-right lg:w-24">Price</th>
                <th class="text-right lg:w-12">Currency</th>
                <th class="text-right lg:w-24">Rate</th>
                <th class="text-right lg:w-36">FG Amount</th>
                <th class="text-right lg:w-36">IDR Amount</th>
                @if ($open)
                <th class="lg:w-16"></th>
                @endif
            </tr>
            </thead>
            <tbody>

            @forelse ($details as $key => $detail)
            @if ($open)
            <tr wire:key="table-row-{{ $detail->id }}" wire:loading.class="cursor-wait" class="divide-x divide-gray-200 dark:divide-gray-900 hover:bg-yellow-50 dark:hover:bg-gray-800 cursor-pointer">
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
            </tr>
            @else
            <tr wire:key="table-row-{{ $detail->id }}" class="divide-x divide-gray-200 dark:divide-gray-900 hover:bg-yellow-50 dark:hover:bg-gray-800">
                <td class="">{{ $detail->serviceCharge->code ?? '' }}; {{ $detail->serviceCharge->name ?? '' }}</td>
                <td class="text-right">{{ Cast::money($detail->qty, 2) }}</td>
                <td class="">{{ $detail->uom->code ?? '' }}</td>
                <td class="text-right">{{ Cast::money($detail->price, 2) }}</td>
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
    <x-drawer wire:model="drawer" title="Create Item" right separator with-close-button class="lg:w-1/3">
        <x-form wire:submit="save">
            <div class="space-y-4">
                {{-- <x-choices-offline
                    label="Items Master"
                    : options="\App\Models\ServiceCharge::query()->isActive()->get()"
                    wire : model="service_charge_id"
                    option-label="full_name"
                    single
                    searchable
                    placeholder="-- Select --"
                /> --}}
                <x-choices
                    label="Items Master"
                    wire:model="service_charge_id"
                    :options="$serviceCharge"
                    search-function="searchServiceCharge"
                    option-label="full_name"
                    single
                    searchable
                    clearable
                    placeholder="-- Select --"
                    :disabled="!$open"
                />
                <div class="space-y-4 lg:space-y-0 lg:grid grid-cols-2 gap-4">
                    <x-choices-offline
                        label="Unit"
                        :options="\App\Models\Uom::query()->isActive()->get()"
                        wire:model="uom_id"
                        option-label="code"
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
                    <x-input label="Qty" wire:model="qty" class="money" />
                    <x-input label="Price" wire:model="price" class="money" />
                    <x-input label="Rate" wire:model="currency_rate" class="money" />
                </div>
                <x-input label="Note" wire:model="note" />
            </div>
            <x-slot:actions>
                <x-button label="Save" icon="o-paper-airplane" type="submit" spinner="save" class="btn-primary" />
            </x-slot:actions>
        </x-form>
    </x-drawer>

    <x-modal wire:model="deliveryOrderModal" title="Select Delivery Order" class="backdrop-blur">
        <div class="space-y-4">
            <x-input
                placeholder="Search by code..."
                wire:model.live.debounce.300ms="deliveryOrderSearch"
                icon="o-magnifying-glass"
                clearable
            />

            {{-- Delivery Order List --}}
            <div class="max-h-64 overflow-y-auto border rounded-lg">
                <table class="table table-sm">
                    <thead class="bg-base-200 sticky top-0">
                        <tr>
                            <th></th>
                            <th>Code</th>
                            <th>Date</th>
                            <th>Contact</th>
                            <th class="text-right">Total Qty</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($deliveryOrders as $do)
                        <tr
                            wire:key="do-{{ $do->id }}"
                            class="hover:bg-base-200 cursor-pointer {{ $selectedDeliveryOrder && $selectedDeliveryOrder->id == $do->id ? 'bg-primary/10' : '' }}"
                            wire:click="selectDeliveryOrder({{ $do->id }})"
                        >
                            <td>
                                <input
                                    type="radio"
                                    name="deliveryOrder"
                                    class="radio radio-sm radio-primary"
                                    {{ $selectedDeliveryOrder && $selectedDeliveryOrder->id == $do->id ? 'checked' : '' }}
                                />
                            </td>
                            <td class="font-semibold">{{ $do->code }}</td>
                            <td>{{ \Carbon\Carbon::parse($do->delivery_date)->format('d-m-Y') }}</td>
                            <td>{{ $do->contact?->name ?? '-' }}</td>
                            <td class="text-right">{{ \App\Helpers\Cast::money($do->total_qty, 2) }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center text-gray-500 py-4">No closed delivery order found for this contact</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Selected Delivery Order Details --}}
            @if($selectedDeliveryOrder)
            <div class="border rounded-lg p-4 bg-base-200">
                <h4 class="font-semibold mb-2">Delivery Order Details: {{ $selectedDeliveryOrder->code }}</h4>
                <div class="max-h-48 overflow-y-auto">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th class="text-right">Qty</th>
                                <th>Unit</th>
                                <th class="text-right">Price</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($selectedDeliveryOrder->details as $detail)
                            <tr wire:key="do-detail-{{ $detail->id }}">
                                <td>
                                    <span class="font-semibold">{{ $detail->serviceCharge?->code ?? '' }}</span>
                                    {{ $detail->serviceCharge?->name ?? '' }}
                                </td>
                                <td class="text-right">{{ \App\Helpers\Cast::money($detail->qty, 2) }}</td>
                                <td>{{ $detail->uom?->code ?? '' }}</td>
                                <td class="text-right">{{ \App\Helpers\Cast::money($detail->price, 2) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif
        </div>

        <x-slot:actions>
            <x-button label="Cancel" icon="o-x-mark" @click="$wire.deliveryOrderModal = false" />
            <x-button
                label="Import Items"
                icon="o-arrow-down-tray"
                wire:click="importFromDeliveryOrder"
                spinner="importFromDeliveryOrder"
                class="btn-primary"
                :disabled="!$selectedDeliveryOrder"
            />
        </x-slot:actions>
    </x-modal>
</div>
