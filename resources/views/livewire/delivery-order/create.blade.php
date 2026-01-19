<?php

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Collection;
use Illuminate\Support\Carbon;
use Livewire\Volt\Component;
use Mary\Traits\Toast;
use App\Helpers\Cast;
use App\Helpers\Code;
use App\Models\DeliveryOrder;
use App\Models\DeliveryOrderDetail;
use App\Models\SalesOrder;
use App\Models\SalesOrderDetail;
use App\Models\Contact;

new class extends Component {
    use Toast;

    public $code = '';
    public $delivery_date = '';
    public $sales_order_id = '';
    public $sales_order_code = '';
    public $contact_id = '';
    public $note = '';
    public $total_qty = 0;
    public $total_amount = 0;

    public Collection $contacts;
    public Collection $orderDetails;
    public array $selectedItems = [];

    public bool $showSOModal = false;

    public function mount(): void
    {
        Gate::authorize('create delivery-order');

        $this->delivery_date = Carbon::now()->format('Y-m-d');

        $this->contacts = Contact::orderBy('name')->get();
        $this->orderDetails = collect();
    }

    public function selectSalesOrder(): void
    {
        if (empty($this->sales_order_id)) {
            $this->error('Please select a Sales Order.');
            return;
        }

        $salesOrder = SalesOrder::with(['details.serviceCharge', 'details.uom', 'contact'])->find($this->sales_order_id);

        if (!$salesOrder) {
            $this->error('Sales Order not found.');
            return;
        }

        $this->sales_order_code = $salesOrder->code;
        $this->contact_id = $salesOrder->contact_id;

        // Get existing delivered qty for each detail
        $deliveredQtys = DeliveryOrderDetail::whereHas('deliveryOrder', function ($q) use ($salesOrder) {
                $q->stored()->where('sales_order_id', $salesOrder->id);
            })
            ->selectRaw('sales_order_detail_id, SUM(qty) as total_delivered')
            ->groupBy('sales_order_detail_id')
            ->pluck('total_delivered', 'sales_order_detail_id')
            ->toArray();

        // Build order details with remaining qty
        $details = [];
        foreach ($salesOrder->details as $detail) {
            $deliveredQty = $deliveredQtys[$detail->id] ?? 0;
            $remainingQty = $detail->qty - $deliveredQty;

            if ($remainingQty > 0) {
                $details[] = [
                    'id' => $detail->id,
                    'service_charge_id' => $detail->service_charge_id,
                    'service_charge_name' => $detail->serviceCharge->name ?? '',
                    'uom_id' => $detail->uom_id,
                    'uom_name' => $detail->uom->name ?? '',
                    'order_qty' => $detail->qty,
                    'delivered_qty' => $deliveredQty,
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
        $this->showSOModal = false;
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
            'delivery_date' => 'required|date',
            'sales_order_id' => 'required',
        ]);

        if ($this->orderDetails->where('selected', true)->isEmpty()) {
            $this->error('Please select at least one item to deliver.');
            return;
        }

        try {
            $delivery = DeliveryOrder::create([
                'code' => Code::auto('DO'),
                'delivery_date' => $this->delivery_date,
                'sales_order_id' => $this->sales_order_id,
                'sales_order_code' => $this->sales_order_code,
                'contact_id' => $this->contact_id,
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
                    DeliveryOrderDetail::create([
                        'delivery_order_id' => $delivery->id,
                        'sales_order_detail_id' => $detail['id'],
                        'service_charge_id' => $detail['service_charge_id'],
                        'uom_id' => $detail['uom_id'],
                        'order_qty' => $detail['order_qty'],
                        'delivered_qty' => $detail['delivered_qty'],
                        'qty' => $detail['qty'],
                        'price' => $detail['price'],
                        'amount' => $detail['amount'],
                    ]);
                }
            }

            // Update SO payment_status (using payment_status as delivered status)
            $salesOrder = SalesOrder::find($this->sales_order_id);
            if ($salesOrder) {
                $salesOrder->recalcPaymentStatus();
            }

            $this->success('Delivery Order successfully created.', redirectTo: route('delivery-order.index'));
        } catch (\Exception $e) {
            $this->error('Failed to save delivery order: ' . $e->getMessage());
        }
    }

    public function with(): array
    {
        $salesOrders = SalesOrder::stored()
            ->closed()
            ->where('payment_status', '!=', 'paid')
            ->with('contact')
            ->orderBy('order_date', 'desc')
            ->get();

        return [
            'salesOrderOptions' => $salesOrders->map(fn($so) => [
                'id' => $so->id,
                'name' => $so->code . ' - ' . ($so->contact->name ?? 'No Contact') . ' (' . Carbon::parse($so->order_date)->format('d-m-Y') . ')',
            ])->toArray(),
        ];
    }
}; ?>

<div>
    <div class="lg:top-[65px] lg:sticky z-10 bg-base-200 pb-0 pt-3">
        <x-header separator>
            <x-slot:title>
                <div class="flex items-center gap-4">
                    <span>Create Delivery Order</span>
                </div>
            </x-slot:title>
            <x-slot:actions>
                <x-button label="Back" link="{{ route('delivery-order.index') }}" icon="o-arrow-uturn-left" class="btn-soft" responsive />
                <x-button label="Save" icon="o-paper-airplane" wire:click.prevent="save" spinner="save" class="btn-primary" responsive />
            </x-slot:actions>
        </x-header>
    </div>

    <div class="space-y-4">
        <x-card>
            <div class="space-y-4">
                <div class="space-y-4 lg:space-y-0 lg:grid grid-cols-3 gap-4">
                    <x-input label="Code" wire:model="code" placeholder="Auto" readonly class="bg-base-200" />
                    <x-datetime label="Delivery Date" wire:model="delivery_date" />
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
                <div>
                        <x-button label="Select Sales Order" icon="o-document-text" @click="$wire.showSOModal = true" class="btn-outline mt-7" />
                        @if($sales_order_code)
                        <span class="ml-2 text-sm text-success">{{ $sales_order_code }}</span>
                        @endif
                    </div>
                <x-textarea label="Note" wire:model="note" rows="2" />
            </div>
        </x-card>

        @if($orderDetails->count() > 0)
        <x-card title="Items to Deliver">
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
                            <th class="text-right">Delivered</th>
                            <th class="text-right">Remaining</th>
                            <th class="text-right w-32">Qty to Deliver</th>
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
                            <td class="text-right">{{ number_format($detail['delivered_qty'], 2) }}</td>
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

    <x-modal wire:model="showSOModal" title="Select Sales Order" box-class="max-w-4xl" persistent>
        <div class="space-y-4">
            <x-choices
                label="Sales Order"
                wire:model="sales_order_id"
                :options="$salesOrderOptions"
                option-label="name"
                option-value="id"
                placeholder="-- Select Sales Order --"
                single
                searchable
            />

            @if($sales_order_id)
                <div class="mt-4 p-4 bg-base-200 rounded-lg">
                    <h4 class="font-semibold mb-2">Sales Order Details</h4>
                    @php
                        $selectedSO = \App\Models\SalesOrder::with(['contact', 'details.serviceCharge', 'details.uom'])->find($sales_order_id);
                    @endphp
                    @if($selectedSO)
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                            <div>
                                <span class="font-medium">Code:</span><br>
                                {{ $selectedSO->code }}
                            </div>
                            <div>
                                <span class="font-medium">Order Date:</span><br>
                                {{ \Carbon\Carbon::parse($selectedSO->order_date)->format('d-m-Y') }}
                            </div>
                            <div>
                                <span class="font-medium">Contact:</span><br>
                                {{ $selectedSO->contact->name ?? 'N/A' }}
                            </div>
                            <div>
                                <span class="font-medium">Status:</span><br>
                                <x-status-badge :status="$selectedSO->status" />
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
                                        @foreach($selectedSO->details as $detail)
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
            <x-button label="Cancel" @click="$wire.showSOModal = false" />
            <x-button label="Select" icon="o-check" wire:click="selectSalesOrder" class="btn-primary" />
        </x-slot:actions>
    </x-modal>
</div>
