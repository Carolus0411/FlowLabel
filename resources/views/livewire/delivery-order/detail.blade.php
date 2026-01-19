<?php

use Illuminate\Support\Collection;
use Livewire\Volt\Component;
use Livewire\Attributes\On;
use Mary\Traits\Toast;
use App\Helpers\Cast;
use App\Rules\Number;
use App\Models\ServiceCharge;
use App\Models\DeliveryOrder;
use App\Models\DeliveryOrderDetail;

new class extends Component {
    use Toast;

    public DeliveryOrder $deliveryOrder;
    public $selected;

    public string $mode = '';
    public bool $drawer = false;
    public bool $open = true;
    public bool $editable = true;

    public $service_charge_id = '';
    public $note = '';
    public $uom_id = '';
    public $order_qty = 0;
    public $delivered_qty = 0;
    public $qty = 0;
    public $price = 0;
    public $amount = 0;

    public Collection $serviceCharges;

    public function mount($id = '', $editable = true): void
    {
        $this->deliveryOrder = DeliveryOrder::find($id);
        $this->editable = $editable;
        $this->serviceCharges = collect();
    }

    public function with(): array
    {
        $this->open = $this->deliveryOrder->status == 'open' && $this->editable;

        $details = $this->deliveryOrder->details()
            ->with(['serviceCharge', 'uom'])
            ->get();

        return [
            'details' => $details
        ];
    }

    public function headers(): array
    {
        return [
            ['key' => 'act', 'label' => '#', 'sortable' => false],
            ['key' => 'serviceCharge.name', 'label' => 'Item', 'sortable' => false],
            ['key' => 'uom.name', 'label' => 'UOM', 'sortable' => false],
            ['key' => 'order_qty', 'label' => 'Order Qty', 'class' => 'text-right', 'format' => ['currency', '2.,', '']],
            ['key' => 'delivered_qty', 'label' => 'Prev Delivered', 'class' => 'text-right', 'format' => ['currency', '2.,', '']],
            ['key' => 'qty', 'label' => 'Qty', 'class' => 'text-right', 'format' => ['currency', '2.,', '']],
            ['key' => 'note', 'label' => 'Note'],
        ];
    }

    public function clearForm(): void
    {
        $this->selected = null;
        $this->service_charge_id = '';
        $this->note = '';
        $this->uom_id = '';
        $this->order_qty = 0;
        $this->delivered_qty = 0;
        $this->qty = 0;
        $this->price = 0;
        $this->amount = 0;
        $this->resetValidation();
    }

    public function edit(DeliveryOrderDetail $detail): void
    {
        if (!$this->open) return;

        $this->clearForm();
        $this->fill($detail);
        $this->selected = $detail;
        $this->mode = 'edit';
        $this->drawer = true;
    }

    public function save(): void
    {
        $data = $this->validate([
            'qty' => ['required', new Number],
            'note' => 'nullable',
        ]);

        $qty = Cast::number($this->qty);
        $price = Cast::number($this->price);
        $amount = $qty * $price;

        $data['qty'] = $qty;
        $data['amount'] = $amount;

        $this->selected->update($data);

        $this->drawer = false;
        $this->dispatchTotals();
        $this->success('Detail updated successfully.');
    }

    public function delete(DeliveryOrderDetail $detail): void
    {
        if (!$this->open) return;

        $detail->delete();
        $this->dispatchTotals();
        $this->success('Detail deleted successfully.');
    }

    public function dispatchTotals(): void
    {
        $totalQty = $this->deliveryOrder->details()->sum('qty');
        $totalAmount = $this->deliveryOrder->details()->sum('amount');

        $this->dispatch('detail-updated', data: [
            'total_qty' => $totalQty,
            'total_amount' => $totalAmount,
        ]);
    }
}; ?>

<div>
    <x-table :headers="$this->headers()" :rows="$details" show-empty-text>
        @scope('cell_act', $detail)
        @if ($this->open)
        <x-dropdown class="btn-xs btn-soft">
            <x-menu-item title="Edit" wire:click="edit({{ $detail->id }})" icon="o-pencil-square" />
            <x-menu-item title="Delete" wire:click="delete({{ $detail->id }})" wire:confirm="Are you sure to delete this item?" icon="o-trash" />
        </x-dropdown>
        @endif
        @endscope
    </x-table>

    <x-drawer wire:model="drawer" :title="$mode == 'add' ? 'Add Detail' : 'Edit Detail'" right separator with-close-button class="lg:w-1/3">
        <div class="space-y-4">
            <x-input label="Item" value="{{ $selected?->serviceCharge?->name ?? '' }}" readonly class="bg-base-200" />
            <x-input label="UOM" value="{{ $selected?->uom?->name ?? '' }}" readonly class="bg-base-200" />
            <x-input label="Order Qty" wire:model="order_qty" readonly class="bg-base-200" />
            <x-input label="Previously Delivered" wire:model="delivered_qty" readonly class="bg-base-200" />
            <x-input label="Qty to Deliver" wire:model="qty" x-mask:dynamic="$money($input, '.', ',')" />
            <x-textarea label="Note" wire:model="note" rows="2" />
        </div>
        <x-slot:actions>
            <x-button label="Cancel" icon="o-x-mark" @click="$wire.drawer = false" class="" />
            <x-button label="Save" icon="o-check" wire:click="save" spinner="save" class="btn-primary" />
        </x-slot:actions>
    </x-drawer>
</div>
