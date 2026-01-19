<?php

use Livewire\Volt\Component;
use Mary\Traits\Toast;
use App\Helpers\Cast;
use App\Rules\Number;
use App\Models\StockAdjustmentOut;
use App\Models\StockAdjustmentOutDetail;
use App\Traits\ServiceChargeChoice;

new class extends Component {
    use Toast, ServiceChargeChoice;

    public StockAdjustmentOut $stockAdjustmentOut;
    public $selected;

    public $mode = '';
    public bool $drawer = false;
    public bool $open = true;

    public $service_charge_id = '';
    public $qty = 0;
    public $price = 0;
    public $amount = 0;
    public $note = '';

    public function mount( $id = '' ): void
    {
        $this->stockAdjustmentOut = StockAdjustmentOut::find($id);
        $this->open = $this->stockAdjustmentOut->status == 'open';
        $this->searchServiceCharge();
    }

    public function with(): array
    {
        return [
            'details' => $this->stockAdjustmentOut->details()->with(['serviceCharge'])->get(),
            'serviceChargeOptions' => $this->serviceChargeChoice,
        ];
    }

    public function clearForm(): void
    {
        $this->selected = null;
        $this->service_charge_id = '';
        $this->qty = '';
        $this->price = '';
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

    public function edit(StockAdjustmentOutDetail $detail): void
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
            'qty' => ['required', new Number],
            'price' => ['required', new Number],
            'note' => 'required',
        ]);

        $qty = Cast::number($this->qty);
        $price = Cast::number($this->price);
        $amount = $qty * $price;

        if ($this->mode == 'add')
        {
            $this->stockAdjustmentOut->details()->create([
                'service_charge_id' => $this->service_charge_id,
                'qty' => $qty,
                'price' => $price,
                'amount' => $amount,
                'note' => $this->note,
            ]);
        }

        if ($this->mode == 'edit')
        {
            $this->selected->update([
                'service_charge_id' => $this->service_charge_id,
                'qty' => $qty,
                'price' => $price,
                'amount' => $amount,
                'note' => $this->note,
            ]);
        }

        $this->clearForm();
        $this->drawer = false;
        $this->success('Product added to adjustment.', position: 'top-end');
    }

    public function delete(StockAdjustmentOutDetail $detail): void
    {
        $detail->delete();
        $this->success('Product removed from adjustment.', position: 'top-end');
    }
}; ?>

<div>
    {{-- Details Table --}}
    <x-card>
        <div class="flex justify-between items-center mb-4">
            <h3 class="font-bold text-lg">Products</h3>
            @if ($open)
            <x-button label="Add Product" icon="o-plus" wire:click="add" class="btn-primary btn-sm" />
            @endif
        </div>

        @if ($details->count())
        <x-table :headers="[
            ['key' => 'serviceCharge.name', 'label' => 'Product', 'sortable' => false],
            ['key' => 'qty', 'label' => 'Qty', 'class' => 'text-right', 'format' => ['currency', '4.,', '']],
            ['key' => 'price', 'label' => 'Price', 'class' => 'text-right', 'format' => ['currency', '2.,', '']],
            ['key' => 'amount', 'label' => 'Amount', 'class' => 'text-right', 'format' => ['currency', '2.,', '']],
            ['key' => 'note', 'label' => 'Note', 'class' => 'max-w-xs truncate'],
            ['key' => 'act', 'label' => '#', 'sortable' => false, 'disableLink' => true],
        ]" :rows="$details">
            @scope('cell_act', $detail)
            @if ($open)
            <div class="flex gap-2">
                <x-button icon="o-pencil" wire:click="edit({{ $detail->id }})" class="btn-sm btn-ghost" />
                <x-button icon="o-trash" wire:click="delete({{ $detail->id }})" wire:confirm="Delete this product?" class="btn-sm btn-ghost text-error" />
            </div>
            @endif
            @endscope
        </x-table>
        @else
        <div class="text-center py-8 text-gray-400">
            <p>No products added yet</p>
        </div>
        @endif
    </x-card>

    {{-- Form Drawer --}}
    <x-drawer wire:model="drawer" title="{{ $mode == 'add' ? 'Add Product' : 'Edit Product' }}" separator with-close-button>
        <x-form wire:submit="save">
            <div class="space-y-4">
                <x-choices-offline
                    label="Product"
                    wire:model="service_charge_id"
                    :options="$serviceChargeOptions"
                    search-function="searchServiceCharge"
                    single
                    searchable
                    required
                />
                <x-input label="Qty" wire:model="qty" type="number" step="0.0001" required />
                <x-input label="Price" wire:model="price" type="number" step="0.01" required />
                <x-textarea label="Note" wire:model="note" required />
            </div>

            <x-slot:actions>
                <x-button label="Cancel" @click="$wire.drawer = false" />
                <x-button label="Save" type="submit" class="btn-primary" />
            </x-slot:actions>
        </x-form>
    </x-drawer>
</div>
