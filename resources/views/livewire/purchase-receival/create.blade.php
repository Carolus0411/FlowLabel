<?php

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Collection;
use Illuminate\Support\Carbon;
use Livewire\Volt\Component;
use Mary\Traits\Toast;
use App\Helpers\Cast;
use App\Helpers\Code;
use App\Models\PurchaseReceival;
use App\Models\PurchaseReceivalDetail;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderDetail;
use App\Models\Supplier;

new class extends Component {
    use Toast;

    public $code = '';
    public $receival_date = '';
    public $purchase_order_id = '';
    public $purchase_order_code = '';
    public $supplier_id = '';
    public $note = '';
    public $total_qty = 0;
    public $total_amount = 0;

    public Collection $purchaseOrders;
    public Collection $suppliers;
    public Collection $orderDetails;
    public array $selectedItems = [];

    public bool $showPOModal = false;

    public function mount(): void
    {
        Gate::authorize('create purchase-receival');

        $this->receival_date = Carbon::now()->format('Y-m-d');

        $this->purchaseOrders = PurchaseOrder::stored()
            ->closed()
            ->where('payment_status', '!=', 'paid')
            ->orderBy('order_date', 'desc')
            ->get();

        $this->suppliers = Supplier::orderBy('name')->get();
        $this->orderDetails = collect();
    }

    public function selectPurchaseOrder(): void
    {
        if (empty($this->purchase_order_id)) {
            $this->error('Please select a Purchase Order.');
            return;
        }

        $purchaseOrder = PurchaseOrder::with(['details.serviceCharge', 'details.uom', 'supplier'])->find($this->purchase_order_id);

        if (!$purchaseOrder) {
            $this->error('Purchase Order not found.');
            return;
        }

        $this->purchase_order_code = $purchaseOrder->code;
        $this->supplier_id = $purchaseOrder->supplier_id;

        // Get existing received qty for each detail
        $receivedQtys = PurchaseReceivalDetail::whereHas('purchaseReceival', function ($q) use ($purchaseOrder) {
                $q->stored()->where('purchase_order_id', $purchaseOrder->id);
            })
            ->selectRaw('purchase_order_detail_id, SUM(qty) as total_received')
            ->groupBy('purchase_order_detail_id')
            ->pluck('total_received', 'purchase_order_detail_id')
            ->toArray();

        // Build order details with remaining qty
        $details = [];
        foreach ($purchaseOrder->details as $detail) {
            $receivedQty = $receivedQtys[$detail->id] ?? 0;
            $remainingQty = $detail->qty - $receivedQty;

            if ($remainingQty > 0) {
                $details[] = [
                    'id' => $detail->id,
                    'service_charge_id' => $detail->service_charge_id,
                    'service_charge_name' => $detail->serviceCharge->name ?? '',
                    'uom_id' => $detail->uom_id,
                    'uom_name' => $detail->uom->name ?? '',
                    'order_qty' => $detail->qty,
                    'received_qty' => $receivedQty,
                    'remaining_qty' => $remainingQty,
                    'qty' => $remainingQty, // Default to remaining qty
                    'price' => $detail->price,
                    'amount' => $remainingQty * $detail->price,
                    'selected' => true,
                ];
            }
        }

        $this->orderDetails = collect($details);
        $this->calculateTotals();
        $this->showPOModal = false;
    }

    public function updateQty($index, $value): void
    {
        $details = $this->orderDetails->toArray();
        $qty = Cast::number($value);
        $remainingQty = $details[$index]['remaining_qty'];

        // Validate qty doesn't exceed remaining
        if ($qty > $remainingQty) {
            $qty = $remainingQty;
        }
        if ($qty < 0) {
            $qty = 0;
        }

        $details[$index]['qty'] = $qty;
        $details[$index]['amount'] = $qty * $details[$index]['price'];

        $this->orderDetails = collect($details);
        $this->calculateTotals();
    }

    public function toggleItem($index): void
    {
        $details = $this->orderDetails->toArray();
        $details[$index]['selected'] = !$details[$index]['selected'];
        $this->orderDetails = collect($details);
        $this->calculateTotals();
    }

    public function calculateTotals(): void
    {
        $totalQty = 0;
        $totalAmount = 0;

        foreach ($this->orderDetails as $detail) {
            if ($detail['selected']) {
                $totalQty += $detail['qty'];
                $totalAmount += $detail['amount'];
            }
        }

        $this->total_qty = Cast::money($totalQty);
        $this->total_amount = Cast::money($totalAmount);
    }

    public function save(): void
    {
        $this->validate([
            'receival_date' => 'required|date',
            'purchase_order_id' => 'required',
        ]);

        if ($this->orderDetails->where('selected', true)->isEmpty()) {
            $this->error('Please select at least one item to receive.');
            return;
        }

        try {
            $receival = PurchaseReceival::create([
                'code' => Code::auto('GR'),
                'receival_date' => $this->receival_date,
                'purchase_order_id' => $this->purchase_order_id,
                'purchase_order_code' => $this->purchase_order_code,
                'supplier_id' => $this->supplier_id,
                'note' => $this->note,
                'total_qty' => Cast::number($this->total_qty),
                'total_amount' => Cast::number($this->total_amount),
                'saved' => 1,
                'status' => 'open',
                'created_by' => auth()->id() ?? 1,
                'updated_by' => auth()->id() ?? 1,
            ]);

            foreach ($this->orderDetails as $detail) {
                if ($detail['selected'] && $detail['qty'] > 0) {
                    PurchaseReceivalDetail::create([
                        'purchase_receival_id' => $receival->id,
                        'purchase_order_detail_id' => $detail['id'],
                        'service_charge_id' => $detail['service_charge_id'],
                        'uom_id' => $detail['uom_id'],
                        'order_qty' => $detail['order_qty'],
                        'received_qty' => $detail['received_qty'],
                        'qty' => $detail['qty'],
                        'price' => $detail['price'],
                        'amount' => $detail['amount'],
                    ]);
                }
            }

            // Update PO payment_status (using payment_status as received status)
            $purchaseOrder = PurchaseOrder::find($this->purchase_order_id);
            if ($purchaseOrder) {
                $purchaseOrder->recalcPaymentStatus();
            }

            $this->success('Receival successfully created.', redirectTo: route('purchase-receival.index'));
        } catch (\Exception $e) {
            $this->error('Failed to save receival: ' . $e->getMessage());
        }
    }

    public function with(): array
    {
        return [
            'purchaseOrderOptions' => $this->purchaseOrders->map(fn($po) => [
                'id' => $po->id,
                'name' => $po->code . ' - ' . ($po->supplier->name ?? 'No Supplier') . ' (' . Carbon::parse($po->order_date)->format('d-m-Y') . ')',
            ])->toArray(),
        ];
    }
}; ?>

<div>
    <div class="lg:top-[65px] lg:sticky z-10 bg-base-200 pb-0 pt-3">
        <x-header separator>
            <x-slot:title>
                <div class="flex items-center gap-4">
                    <span>Create Purchase Receival</span>
                </div>
            </x-slot:title>
            <x-slot:actions>
                <x-button label="Back" link="{{ route('purchase-receival.index') }}" icon="o-arrow-uturn-left" class="btn-soft" responsive />
                <x-button label="Save" icon="o-paper-airplane" wire:click.prevent="save" spinner="save" class="btn-primary" responsive />
            </x-slot:actions>
        </x-header>
    </div>

    <div class="space-y-4">
        <x-card>
            <div class="space-y-4">
                <div class="space-y-4 lg:space-y-0 lg:grid grid-cols-3 gap-4">
                    <x-input label="Code" wire:model="code" placeholder="Auto" readonly class="bg-base-200" />
                    <x-datetime label="Receival Date" wire:model="receival_date" />
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
                <div>
                        <x-button label="Select Purchase Order" icon="o-document-text" @click="$wire.showPOModal = true" class="btn-outline mt-7" />
                        @if($purchase_order_code)
                        <span class="ml-2 text-sm text-success">{{ $purchase_order_code }}</span>
                        @endif
                    </div>
                <x-textarea label="Note" wire:model="note" rows="2" />
            </div>
        </x-card>

        @if($orderDetails->count() > 0)
        <x-card title="Items to Receive">
            <div class="overflow-x-auto">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th class="w-10">
                                <x-checkbox wire:click="toggleAll" />
                            </th>
                            <th>Item</th>
                            <th>UOM</th>
                            <th class="text-right">Order Qty</th>
                            <th class="text-right">Received</th>
                            <th class="text-right">Remaining</th>
                            <th class="text-right w-32">Qty to Receive</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($orderDetails as $index => $detail)
                        <tr class="{{ $detail['selected'] ? '' : 'opacity-50' }}">
                            <td>
                                <x-checkbox :checked="$detail['selected']" wire:click="toggleItem({{ $index }})" />
                            </td>
                            <td>{{ $detail['service_charge_name'] }}</td>
                            <td>{{ $detail['uom_name'] }}</td>
                            <td class="text-right">{{ number_format($detail['order_qty'], 2) }}</td>
                            <td class="text-right">{{ number_format($detail['received_qty'], 2) }}</td>
                            <td class="text-right">{{ number_format($detail['remaining_qty'], 2) }}</td>
                            <td class="text-right">
                                <input
                                    type="text"
                                    class="input input-sm input-bordered w-24 text-right"
                                    value="{{ $detail['qty'] }}"
                                    wire:change="updateQty({{ $index }}, $event.target.value)"
                                    {{ $detail['selected'] ? '' : 'disabled' }}
                                />
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="font-bold">
                            <td colspan="6" class="text-right">Total:</td>
                            <td class="text-right">{{ $total_qty }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </x-card>
        @endif
    </div>

    <x-modal wire:model="showPOModal" title="Select Purchase Order" box-class="max-w-4xl" persistent>
        <div class="space-y-4">
            <x-choices
                label="Purchase Order"
                wire:model="purchase_order_id"
                :options="$purchaseOrderOptions"
                option-label="name"
                option-value="id"
                placeholder="-- Select Purchase Order --"
                single
                searchable
            />

            @if($purchase_order_id)
                <div class="mt-4 p-4 bg-base-200 rounded-lg">
                    <h4 class="font-semibold mb-2">Purchase Order Details</h4>
                    @php
                        $selectedPO = \App\Models\PurchaseOrder::with(['supplier', 'details.serviceCharge', 'details.uom'])->find($purchase_order_id);
                    @endphp
                    @if($selectedPO)
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                            <div>
                                <span class="font-medium">Code:</span><br>
                                {{ $selectedPO->code }}
                            </div>
                            <div>
                                <span class="font-medium">Order Date:</span><br>
                                {{ \Carbon\Carbon::parse($selectedPO->order_date)->format('d-m-Y') }}
                            </div>
                            <div>
                                <span class="font-medium">Supplier:</span><br>
                                {{ $selectedPO->supplier->name ?? 'N/A' }}
                            </div>
                            <div>
                                <span class="font-medium">Status:</span><br>
                                <x-status-badge :status="$selectedPO->status" />
                            </div>
                        </div>

                        <div class="mt-4">
                            <h5 class="font-medium mb-2">Items:</h5>
                            <div class="overflow-x-auto">
                                <table class="table table-xs">
                                    <thead>
                                        <tr>
                                            <th>Item</th>
                                            <th>UOM</th>
                                            <th class="text-right">Qty</th>
                                            <th class="text-right">Price</th>
                                            <th class="text-right">Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($selectedPO->details as $detail)
                                        <tr>
                                            <td>{{ $detail->serviceCharge->name ?? 'N/A' }}</td>
                                            <td>{{ $detail->uom->name ?? 'N/A' }}</td>
                                            <td class="text-right">{{ number_format($detail->qty, 2) }}</td>
                                            <td class="text-right">{{ number_format($detail->price, 2) }}</td>
                                            <td class="text-right">{{ number_format($detail->amount, 2) }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif
                </div>
            @endif
        </div>
        <x-slot:actions>
            <x-button label="Cancel" @click="$wire.showPOModal = false" />
            <x-button label="Select" icon="o-check" wire:click="selectPurchaseOrder" class="btn-primary" />
        </x-slot:actions>
    </x-modal>
</div>
