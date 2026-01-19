<?php

use Illuminate\Support\Facades\Gate;
use Livewire\Volt\Component;
use Mary\Traits\Toast;
use App\Models\OrderLabel;

new class extends Component {
    use Toast;

    public OrderLabel $orderLabel;

    public $code = '';
    public $order_date = '';
    public $note = '';
    public $status = 'open';
    public $original_filename = '';
    public $split_filename = '';
    public $page_number = '';
    public $extracted_text = '';

    public function mount(OrderLabel $orderLabel): void
    {
        Gate::authorize('view order-label');

        $this->orderLabel = $orderLabel;

        if (!$this->orderLabel || !$this->orderLabel->exists) {
            $this->error('Order label not found');
            return;
        }

        $this->fill($this->orderLabel->toArray());
    }

    public function save(): void
    {
        $data = $this->validate([
            'code' => 'required|string|max:255',
            'order_date' => 'required|date',
            'note' => 'nullable|string',
            'status' => 'required|in:open,close,void',
        ]);

        $this->orderLabel->update([
            ...$data,
            'updated_by' => auth()->id(),
        ]);

        $this->success('Order label updated successfully!', redirectTo: route('order-label.index'));
    }

    public function delete(): void
    {
        Gate::authorize('view order-label');

        $this->orderLabel->delete();
        $this->success('Order label deleted successfully!', redirectTo: route('order-label.index'));
    }
};
?>
        $data['order_amount'] = Cast::number($this->order_amount);
        $data['saved'] = 1;
        $data['updated_by'] = auth()->user()->id ?? 1;

        try {
            $this->salesOrder->update($data);
            $this->success('Order successfully updated.', redirectTo: route('sales-order.index'));
        } catch (\Exception $e) {
            $this->error('Failed to save order: ' . $e->getMessage());
        }
    }

    public function voidOrder(): void
    {
        $this->salesOrder->update(['status' => 'void']);
        $this->success('Order successfully voided.', redirectTo: route('sales-order.index'));
    }

    public function close(): void
    {
        // Gate::authorize('close sales-order');
        $this->salesOrder->update(['status' => 'close']);
        $this->closeConfirm = false;
        $this->success('Order successfully approved.', redirectTo: route('sales-order.index'));
    }

    public function delete(): void
    {
        $this->salesOrder->delete();
        $this->success('Order successfully deleted.', redirectTo: route('sales-order.index'));
    }

    public function calculate()
    {
        $ppn = Ppn::find($this->ppn_id);
        $pph = Pph::find($this->pph_id);
        $ppn_value = $ppn->value ?? 0;
        $pph_value = $pph->value ?? 0;
        $dpp_amount = Cast::number($this->dpp_amount);
        $stamp_amount = Cast::number($this->stamp_amount);

        $ppn_amount = round(($ppn_value/100) * $dpp_amount, 2);
        $pph_amount = round(($pph_value/100) * $dpp_amount, 2);
        $order_amount = $dpp_amount + $ppn_amount + $stamp_amount;

        $this->ppn_amount = Cast::money($ppn_amount);
        $this->pph_amount = Cast::money($pph_amount);
        $this->order_amount = Cast::money($order_amount);
    }

    #[On('detail-updated')]
    public function detailUpdated(array $data = [])
    {
        $this->dpp_amount = Cast::money($data['dpp_amount'] ?? 0);
        $this->calculate();
    }

    public function updated($property, $value): void
    {
        if ( in_array($property, ['ppn_id','pph_id','stamp_amount']))
        {
            $this->calculate();
        }
    }
}; ?>

<div>
    <x-header title="Edit Order Label" separator>
        <x-slot:actions>
            <x-button label="Back" link="{{ route('order-label.index') }}" icon="o-arrow-uturn-left" class="btn-soft" responsive />
            @if($orderLabel->file_path)
                <a href="{{ route('order-label.download', ['path' => urlencode($orderLabel->file_path)]) }}"
                   class="btn btn-primary btn-sm">
                    <x-icon name="o-arrow-down-tray" class="w-4 h-4 mr-1" />
                    Download PDF
                </a>
            @endif
            <x-button label="Delete" wire:click="delete" icon="o-trash" class="btn-error"
                     wire:confirm="Are you sure you want to delete this order label?" responsive />
        </x-slot:actions>
    </x-header>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2">
            <x-card title="Order Label Information">
                <x-form wire:submit="save">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <x-input label="Code" wire:model="code" />
                        <x-datetime label="Order Date" wire:model="order_date" />
                        <x-select label="Status" wire:model="status" :options="[
                            ['id' => 'open', 'name' => 'Open'],
                            ['id' => 'close', 'name' => 'Close'],
                            ['id' => 'void', 'name' => 'Void']
                        ]" />
                    </div>

                    <x-textarea label="Note" wire:model="note" rows="3" />

                    <x-slot:actions>
                        <x-button label="Cancel" link="{{ route('order-label.index') }}" />
                        <x-button label="Save" icon="o-paper-airplane" spinner="save" type="submit" class="btn-primary" />
                    </x-slot:actions>
                </x-form>
            </x-card>
        </div>

        <div>
            <x-card title="PDF Information">
                @if($original_filename)
                    <div class="mb-4">
                        <label class="text-sm font-medium text-gray-600">Original File</label>
                        <p class="text-sm text-gray-900">{{ $original_filename }}</p>
                    </div>
                @endif

                @if($split_filename)
                    <div class="mb-4">
                        <label class="text-sm font-medium text-gray-600">Split File</label>
                        <p class="text-sm text-gray-900">{{ $split_filename }}</p>
                    </div>
                @endif

                @if($page_number)
                    <div class="mb-4">
                        <label class="text-sm font-medium text-gray-600">Page Number</label>
                        <x-badge value="{{ $page_number }}" class="badge-primary" />
                    </div>
                @endif
            </x-card>

            @if($extracted_text)
                <x-card title="Extracted Text" class="mt-4">
                    <div class="bg-gray-50 p-3 rounded text-sm max-h-60 overflow-y-auto">
                        <pre class="whitespace-pre-wrap">{{ Str::limit($extracted_text, 500) }}</pre>
                        @if(strlen($extracted_text) > 500)
                            <p class="text-xs text-gray-500 mt-2">... and {{ strlen($extracted_text) - 500 }} more characters</p>
                        @endif
                    </div>
                </x-card>
            @endif
        </div>
    </div>
</div>
            <x-slot:title>
                <div class="flex items-center gap-4">
                    <span>Update Sales Order</span>
                    <x-status-badge :status="$salesOrder->status" class="uppercase text-sm!" />
                </div>
            </x-slot:title>
            <x-slot:actions>
                <x-button label="Back" link="{{ route('sales-order.index') }}" icon="o-arrow-uturn-left" class="btn-soft" responsive />
                @if ($salesOrder->status == 'open')
                <x-button label="Approve" icon="o-check" @click="$wire.closeConfirm=true" class="btn-success" responsive />
                @endif
                <x-button label="Save" icon="o-paper-airplane" wire:click.prevent="save" spinner="save" class="btn-primary" responsive />
            </x-slot:actions>
        </x-header>
    </div>

    <div class="space-y-4">
        <x-card>
            <div class="space-y-4">
                <div class="space-y-4 lg:space-y-0 lg:grid grid-cols-3 gap-4">
                    <x-input label="Code" wire:model="code" placeholder="Auto" readonly class="bg-base-200" />
                    <x-datetime label="Order Date" wire:model="order_date" />
                    <x-datetime label="Due Date" wire:model="due_date" />
                    <x-select label="Order Type" wire:model="invoice_type" :options="[['id' => 'SO','name' => 'SO']]" placeholder="-- Select --" />
                    <x-choices
                        label="Contact"
                        wire:model="contact_id"
                        :options="$contacts"
                        search-function="searchContact"
                        option-label="name"
                        single
                        searchable
                        clearable
                        placeholder="-- Select --"
                    />
                    <x-input label="Top" wire:model="top" />
                    <x-input label="Note" wire:model="note" />
                    <x-input label="DPP" wire:model="dpp_amount" readonly class="bg-base-200" x-mask:dynamic="$money($input,'.',',')" />
                    <x-choices
                        label="PPN"
                        wire:model.live="ppn_id"
                        :options="$ppns"
                        search-function="searchPpn"
                        option-label="name"
                        single
                        searchable
                        clearable
                        placeholder="-- Select --"
                    />
                    <x-choices
                        label="PPH"
                        wire:model.live="pph_id"
                        :options="$pphs"
                        search-function="searchPph"
                        option-label="name"
                        single
                        searchable
                        clearable
                        placeholder="-- Select --"
                    />
                    <x-input label="Stamp" wire:model.live.debounce.400ms="stamp_amount" class="money" />
                    <x-input label="PPN Amount" wire:model="ppn_amount" readonly class="bg-base-200" />
                    <x-input label="PPH Amount" wire:model="pph_amount" readonly class="bg-base-200" />
                    <x-input label="Order Amount" wire:model="order_amount" readonly class="bg-base-200" />
                </div>
            </div>
        </x-card>

        <div class="overflow-x-auto">
            <livewire:sales-order.detail
                :id="$salesOrder->id ?? 'new'"
            />
        </div>
    </div>

    <x-modal wire:model="closeConfirm" title="Closing Confirmation" persistent>
        <div class="flex pb-2">
            Are you sure you want to approve this order?
        </div>
        <x-slot:actions>
            <div class="flex items-center gap-4">
                <x-button label="Cancel" icon="o-x-mark" @click="$wire.closeConfirm = false" class="" />
                <x-button label="Yes, I am sure" icon="o-check" wire:click="close" spinner="close" class="" />
            </div>
        </x-slot:actions>
    </x-modal>
</div>
