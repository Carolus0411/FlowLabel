<?php

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Collection;
use Livewire\Volt\Component;
use Livewire\Attributes\On;
use Mary\Traits\Toast;
use App\Helpers\Cast;
use App\Models\DeliveryOrder;
use App\Models\DeliveryOrderDetail;
use App\Models\SalesOrder;
use App\Models\Contact;

new class extends Component {
    use Toast;

    public DeliveryOrder $deliveryOrder;

    public $code = '';
    public $delivery_date = '';
    public $sales_order_id = '';
    public $sales_order_code = '';
    public $contact_id = '';
    public $note = '';
    public $total_qty = 0;
    public $total_amount = 0;

    public Collection $contacts;
    public bool $closeConfirm = false;

    public function mount(DeliveryOrder $deliveryOrder): void
    {
        Gate::authorize('update delivery-order');

        $this->deliveryOrder = $deliveryOrder;

        if (!$this->deliveryOrder || !$this->deliveryOrder->exists) {
            $this->error('Delivery order not found');
            return;
        }

        $this->code = $deliveryOrder->code;
        $this->delivery_date = $deliveryOrder->delivery_date;
        $this->sales_order_id = $deliveryOrder->sales_order_id;
        $this->sales_order_code = $deliveryOrder->sales_order_code;
        $this->contact_id = $deliveryOrder->contact_id;
        $this->note = $deliveryOrder->note;
        $this->total_qty = Cast::money($deliveryOrder->total_qty);
        $this->total_amount = Cast::money($deliveryOrder->total_amount);

        $this->contacts = Contact::orderBy('name')->get();
    }

    public function save(): void
    {
        $data = $this->validate([
            'delivery_date' => 'required|date',
            'note' => 'nullable',
        ]);

        $data['updated_by'] = auth()->id() ?? 1;

        try {
            $this->deliveryOrder->update($data);
            $this->deliveryOrder->recalcTotals();
            $this->success('Delivery Order successfully updated.', redirectTo: route('delivery-order.index'));
        } catch (\Exception $e) {
            $this->error('Failed to save delivery order: ' . $e->getMessage());
        }
    }

    public function voidOrder(): void
    {
        $this->deliveryOrder->update(['status' => 'void']);

        \App\Events\DeliveryOrderVoided::dispatch($this->deliveryOrder);

        // Recalc SO payment status
        if ($this->deliveryOrder->sales_order_id) {
            $salesOrder = SalesOrder::find($this->deliveryOrder->sales_order_id);
            if ($salesOrder) {
                $salesOrder->recalcPaymentStatus();
            }
        }

        $this->success('Delivery Order successfully voided.', redirectTo: route('delivery-order.index'));
    }

    public function close(): void
    {
        Gate::authorize('close delivery-order');
        $this->deliveryOrder->update(['status' => 'close']);

        \App\Events\DeliveryOrderClosed::dispatch($this->deliveryOrder);

        $this->closeConfirm = false;
        $this->success('Delivery Order successfully approved.', redirectTo: route('delivery-order.index'));
    }

    public function delete(): void
    {
        // Delete details first
        $this->deliveryOrder->details()->delete();
        $this->deliveryOrder->delete();

        // Recalc SO payment status
        if ($this->deliveryOrder->sales_order_id) {
            $salesOrder = SalesOrder::find($this->deliveryOrder->sales_order_id);
            if ($salesOrder) {
                $salesOrder->recalcPaymentStatus();
            }
        }

        $this->success('Delivery Order successfully deleted.', redirectTo: route('delivery-order.index'));
    }

    #[On('detail-updated')]
    public function detailUpdated(array $data = [])
    {
        $this->total_qty = Cast::money($data['total_qty'] ?? 0);
        $this->total_amount = Cast::money($data['total_amount'] ?? 0);
    }

    public function with(): array
    {
        $open = $this->deliveryOrder->status == 'open';

        return [
            'open' => $open,
        ];
    }
}; ?>

<div>
    <div class="lg:top-[65px] lg:sticky z-10 bg-base-200 pb-0 pt-3">
        <x-header separator>
            <x-slot:title>
                <div class="flex items-center gap-4">
                    <span>Update Delivery Order</span>
                    <x-status-badge :status="$deliveryOrder->status" class="uppercase text-sm!" />
                </div>
            </x-slot:title>
            <x-slot:actions>
                <x-button label="Back" link="{{ route('delivery-order.index') }}" icon="o-arrow-uturn-left" class="btn-soft" responsive />
                @if ($deliveryOrder->status == 'open')
                <x-button label="Approve" icon="o-check" @click="$wire.closeConfirm=true" class="btn-success" responsive />
                @endif
                @if ($open)
                <x-button label="Save" icon="o-paper-airplane" wire:click.prevent="save" spinner="save" class="btn-primary" responsive />
                @endif
            </x-slot:actions>
        </x-header>
    </div>

    <div class="space-y-4">
        <x-card>
            <div class="space-y-4">
                <div class="space-y-4 lg:space-y-0 lg:grid grid-cols-3 gap-4">
                    <x-input label="Code" wire:model="code" readonly class="bg-base-200" />
                    <x-datetime label="Delivery Date" wire:model="delivery_date" :disabled="!$open" />
                    <x-input label="SO Code" wire:model="sales_order_code" readonly class="bg-base-200" />
                </div>
                <div class="space-y-4 lg:space-y-0 lg:grid grid-cols-1 gap-4">
                    <x-choices
                        label="Contact"
                        wire:model="contact_id"
                        :options="$contacts"
                        option-label="name"
                        option-value="id"
                        placeholder="-- Select --"
                        single
                        searchable
                        disabled
                        class="bg-base-200"
                    />
                </div>
                <x-textarea label="Note" wire:model="note" rows="2" :disabled="!$open" />
            </div>
        </x-card>

        <x-card title="Delivered Items">
            <div class="overflow-x-auto">
                <livewire:delivery-order.detail
                    :id="$deliveryOrder->id"
                    :editable="$open"
                />
            </div>
        </x-card>

        <x-card>
            <div class="space-y-4 lg:space-y-0 lg:grid grid-cols-4 gap-4">
                <x-input label="Total Qty" wire:model="total_qty" readonly class="bg-base-200" />
            </div>
        </x-card>
    </div>

    <x-modal wire:model="closeConfirm" title="Closing Confirmation" persistent>
        <div class="flex pb-2">
            Are you sure you want to approve this delivery order?
        </div>
        <x-slot:actions>
            <div class="flex items-center gap-4">
                <x-button label="Cancel" icon="o-x-mark" @click="$wire.closeConfirm = false" class="" />
                <x-button label="Yes, I am sure" icon="o-check" wire:click="close" spinner="close" class="" />
            </div>
        </x-slot:actions>
    </x-modal>
</div>
