<?php

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Livewire\Volt\Component;
use Livewire\Attributes\On;
use Mary\Traits\Toast;
use App\Helpers\Cast;
use App\Models\PurchaseReceival;
use App\Models\PurchaseReceivalDetail;
use App\Models\PurchaseOrder;
use App\Models\Supplier;

new class extends Component {
    use Toast;

    public PurchaseReceival $purchaseReceival;

    public $code = '';
    public $receival_date = '';
    public $purchase_order_id = '';
    public $purchase_order_code = '';
    public $supplier_id = '';
    public $note = '';
    public $total_qty = 0;
    public $total_amount = 0;

    public Collection $suppliers;
    public bool $closeConfirm = false;

    public function mount(PurchaseReceival $purchaseReceival): void
    {
        Gate::authorize('update purchase-receival');

        $this->purchaseReceival = $purchaseReceival;

        if (!$this->purchaseReceival || !$this->purchaseReceival->exists) {
            $this->error('Purchase receival not found');
            return;
        }

        $this->code = $purchaseReceival->code;
        $this->receival_date = $purchaseReceival->receival_date;
        $this->purchase_order_id = $purchaseReceival->purchase_order_id;
        $this->purchase_order_code = $purchaseReceival->purchase_order_code;
        $this->supplier_id = $purchaseReceival->supplier_id;
        $this->note = $purchaseReceival->note;
        $this->total_qty = Cast::money($purchaseReceival->total_qty);
        $this->total_amount = Cast::money($purchaseReceival->total_amount);

        $this->suppliers = Supplier::orderBy('name')->get();
    }

    public function save(): void
    {
        $data = $this->validate([
            'receival_date' => 'required|date',
            'note' => 'nullable',
        ]);

        $data['updated_by'] = auth()->id() ?? 1;

        try {
            $this->purchaseReceival->update($data);
            $this->purchaseReceival->recalcTotals();
            $this->success('Receival successfully updated.', redirectTo: route('purchase-receival.index'));
        } catch (\Exception $e) {
            $this->error('Failed to save receival: ' . $e->getMessage());
        }
    }

    public function voidReceival(): void
    {
        DB::transaction(function () {
            $this->purchaseReceival->update(['status' => 'void']);

            // Remove ledger entries
            \App\Models\InventoryLedger::where('reference_type', \App\Models\PurchaseReceival::class)
                ->where('reference_id', $this->purchaseReceival->id)
                ->delete();

            // Recalc PO payment status
            if ($this->purchaseReceival->purchase_order_id) {
                $purchaseOrder = PurchaseOrder::find($this->purchaseReceival->purchase_order_id);
                if ($purchaseOrder) {
                    $purchaseOrder->recalcPaymentStatus();
                }
            }
        });

        $this->success('Receival successfully voided.', redirectTo: route('purchase-receival.index'));
    }

    public function close(): void
    {
        Gate::authorize('close purchase-receival');

        DB::transaction(function () {
            $this->purchaseReceival->update(['status' => 'close']);

            foreach ($this->purchaseReceival->details as $detail) {
                \App\Models\InventoryLedger::create([
                    'date' => $this->purchaseReceival->receival_date,
                    'service_charge_id' => $detail->service_charge_id,
                    'qty' => $detail->qty,
                    'price' => $detail->price,
                    'type' => 'in',
                    'reference_type' => \App\Models\PurchaseReceival::class,
                    'reference_id' => $this->purchaseReceival->id,
                ]);
            }
        });

        $this->closeConfirm = false;
        $this->success('Receival successfully approved.', redirectTo: route('purchase-receival.index'));
    }

    public function delete(): void
    {
        // Delete details first
        $this->purchaseReceival->details()->delete();
        $this->purchaseReceival->delete();

        // Recalc PO payment status
        if ($this->purchaseReceival->purchase_order_id) {
            $purchaseOrder = PurchaseOrder::find($this->purchaseReceival->purchase_order_id);
            if ($purchaseOrder) {
                $purchaseOrder->recalcPaymentStatus();
            }
        }

        $this->success('Receival successfully deleted.', redirectTo: route('purchase-receival.index'));
    }

    #[On('detail-updated')]
    public function detailUpdated(array $data = [])
    {
        $this->total_qty = Cast::money($data['total_qty'] ?? 0);
        $this->total_amount = Cast::money($data['total_amount'] ?? 0);
    }

    public function with(): array
    {
        $open = $this->purchaseReceival->status == 'open';

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
                    <span>Update Purchase Receival</span>
                    <x-status-badge :status="$purchaseReceival->status" class="uppercase text-sm!" />
                </div>
            </x-slot:title>
            <x-slot:actions>
                <x-button label="Back" link="{{ route('purchase-receival.index') }}" icon="o-arrow-uturn-left" class="btn-soft" responsive />
                @if ($purchaseReceival->status == 'open')
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
                    <x-datetime label="Receival Date" wire:model="receival_date" :disabled="!$open" />
                    <x-input label="PO Code" wire:model="purchase_order_code" readonly class="bg-base-200" />
                </div>
                <div class="space-y-4 lg:space-y-0 lg:grid grid-cols-1 gap-4">
                    <x-choices
                        label="Supplier"
                        wire:model="supplier_id"
                        :options="$suppliers"
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

        <x-card title="Received Items">
            <div class="overflow-x-auto">
                <livewire:purchase-receival.detail
                    :id="$purchaseReceival->id"
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
            Are you sure you want to approve this receival?
        </div>
        <x-slot:actions>
            <div class="flex items-center gap-4">
                <x-button label="Cancel" icon="o-x-mark" @click="$wire.closeConfirm = false" class="" />
                <x-button label="Yes, I am sure" icon="o-check" wire:click="close" spinner="close" class="" />
            </div>
        </x-slot:actions>
    </x-modal>
</div>
