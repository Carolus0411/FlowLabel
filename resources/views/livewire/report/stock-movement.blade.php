<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use App\Models\InventoryLedger;
use App\Models\ServiceCharge;
use App\Models\PurchaseOrder;
use App\Models\PurchaseReceival;
use App\Models\SalesOrder;
use App\Models\DeliveryOrder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

new #[Layout('components.layouts.app')] class extends Component {
    public $start_date;
    public $end_date;
    public $service_charge_id = '';

    public function mount()
    {
        $this->start_date = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->end_date = Carbon::now()->endOfMonth()->format('Y-m-d');
    }

    public function getItemsProperty()
    {
        return ServiceCharge::query()
            ->with(['itemType'])
            ->whereHas('itemType', function($query) {
                $query->where('is_stock', true);
            })
            ->orderBy('code')
            ->get()
            ->map(fn($item) => [
                'id' => $item->id,
                'name' => $item->code . ' - ' . $item->name,
            ]);
    }

    public function getMovementData()
    {
        if (!$this->service_charge_id) {
            return [];
        }

        $startDate = Carbon::parse($this->start_date)->startOfDay();
        $endDate = Carbon::parse($this->end_date)->endOfDay();

        $movements = InventoryLedger::where('service_charge_id', $this->service_charge_id)
            ->whereBetween('date', [$startDate, $endDate])
            ->with(['serviceCharge'])
            ->orderBy('date')
            ->orderBy('id')
            ->get();

        $data = [];
        foreach ($movements as $movement) {
            $transactionNo = '';
            $referenceNo = '';

            // Use direct columns if available (for Production etc)
            if (!empty($movement->transaction_source)) {
                $transactionNo = $movement->transaction_source . ': ' . $movement->reference_number;
                $referenceNo = $movement->reference_number;
            }
            // Fallback to polymorphic resolution
            elseif ($movement->reference_type && $movement->reference_id) {
                $referenceModel = app($movement->reference_type)->find($movement->reference_id);

                if ($referenceModel) {
                    // For Purchase Receival
                    if ($movement->reference_type === 'App\Models\PurchaseReceival') {
                        $transactionNo = 'GR: ' . $referenceModel->code;
                        if ($referenceModel->purchase_order_id) {
                            $po = PurchaseOrder::find($referenceModel->purchase_order_id);
                            $referenceNo = $po ? 'PO: ' . $po->code : '';
                        }
                    }
                    // For Delivery Order
                    elseif ($movement->reference_type === 'App\Models\DeliveryOrder') {
                        $transactionNo = 'DO: ' . $referenceModel->code;
                        if ($referenceModel->sales_order_id) {
                            $so = SalesOrder::find($referenceModel->sales_order_id);
                            $referenceNo = $so ? 'SO: ' . $so->code : '';
                        }
                    }
                    // For Production (Legacy or if columns empty)
                    elseif ($movement->reference_type === 'App\Models\Production') {
                        $transactionNo = 'Production: ' . $referenceModel->code;
                        $referenceNo = $referenceModel->code;
                    }
                }
            }

            $data[] = [
                'date' => $movement->date,
                'transaction_no' => $transactionNo,
                'reference_no' => $referenceNo,
                'type' => $movement->type,
                'qty_in' => $movement->type === 'in' ? abs($movement->qty) : 0,
                'qty_out' => $movement->type === 'out' ? abs($movement->qty) : 0,
                'price' => $movement->price,
            ];
        }

        return $data;
    }

    public function getSelectedItemProperty()
    {
        if (!$this->service_charge_id) {
            return null;
        }
        return ServiceCharge::find($this->service_charge_id);
    }

    public function getOpeningStockProperty()
    {
        if (!$this->service_charge_id) {
            return 0;
        }

        $startDate = Carbon::parse($this->start_date)->startOfDay();

        return InventoryLedger::where('service_charge_id', $this->service_charge_id)
            ->where('date', '<', $startDate)
            ->sum('qty');
    }
}; ?>

<div>
    <x-header title="Report Stock Movement" separator />

    <div class="mb-4 grid grid-cols-1 md:grid-cols-4 gap-4">
        <x-datepicker label="Start Date" wire:model.live="start_date" />
        <x-datepicker label="End Date" wire:model.live="end_date" />
        <div class="md:col-span-2">
            <x-select
                label="Item"
                wire:model.live="service_charge_id"
                :options="$this->items"
                placeholder="Select item to view movement"
            />
        </div>
    </div>

    @if($this->selectedItem)
    <x-card class="mb-4">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <div class="text-sm text-gray-500">Item Code</div>
                <div class="font-semibold">{{ $this->selectedItem->code }}</div>
            </div>
            <div>
                <div class="text-sm text-gray-500">Item Name</div>
                <div class="font-semibold">{{ $this->selectedItem->name }}</div>
            </div>
            <div>
                <div class="text-sm text-gray-500">Opening Stock</div>
                <div class="font-semibold">{{ number_format($this->openingStock, 3) }}</div>
            </div>
        </div>
    </x-card>
    @endif

    <x-card>
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold">Stock Movement Details</h3>
            <x-button label="Print" icon="o-printer" class="btn-primary btn-sm" onclick="window.print()" />
        </div>

        <div class="overflow-x-auto">
            <table class="table table-zebra table-sm">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Transaction</th>
                        <th>Reference</th>
                        <th class="text-right">Qty In</th>
                        <th class="text-right">Qty Out</th>
                        <th class="text-right">Price</th>
                        <th class="text-right">Value</th>
                        <th class="text-right">Running Balance</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $runningBalance = $this->openingStock;
                        $totalIn = 0;
                        $totalOut = 0;
                        $totalValue = 0;
                    @endphp

                    @if($this->openingStock != 0 && $this->service_charge_id)
                    <tr class="font-semibold bg-base-200">
                        <td colspan="3">Opening Balance</td>
                        <td class="text-right">-</td>
                        <td class="text-right">-</td>
                        <td class="text-right">-</td>
                        <td class="text-right">-</td>
                        <td class="text-right">{{ number_format($runningBalance, 3) }}</td>
                    </tr>
                    @endif

                    @forelse($this->getMovementData() as $row)
                        @php
                            $runningBalance += ($row['qty_in'] - $row['qty_out']);
                            $totalIn += $row['qty_in'];
                            $totalOut += $row['qty_out'];
                            $value = ($row['qty_in'] > 0 ? $row['qty_in'] : $row['qty_out']) * $row['price'];
                            $totalValue += $value;
                        @endphp
                        <tr>
                            <td>{{ Carbon::parse($row['date'])->format('d/m/Y') }}</td>
                            <td>{{ $row['transaction_no'] }}</td>
                            <td>{{ $row['reference_no'] }}</td>
                            <td class="text-right">{{ $row['qty_in'] > 0 ? number_format($row['qty_in'], 3) : '-' }}</td>
                            <td class="text-right">{{ $row['qty_out'] > 0 ? number_format($row['qty_out'], 3) : '-' }}</td>
                            <td class="text-right">{{ number_format($row['price'], 2) }}</td>
                            <td class="text-right">{{ number_format($value, 2) }}</td>
                            <td class="text-right">{{ number_format($runningBalance, 3) }}</td>
                        </tr>
                    @empty
                        @if($this->service_charge_id)
                        <tr>
                            <td colspan="8" class="text-center">No movement data for selected period</td>
                        </tr>
                        @else
                        <tr>
                            <td colspan="8" class="text-center">Please select an item to view stock movement</td>
                        </tr>
                        @endif
                    @endforelse

                    @if(count($this->getMovementData()) > 0)
                    <tr class="font-semibold bg-base-200">
                        <td colspan="3">Total</td>
                        <td class="text-right">{{ number_format($totalIn, 3) }}</td>
                        <td class="text-right">{{ number_format($totalOut, 3) }}</td>
                        <td class="text-right">-</td>
                        <td class="text-right">{{ number_format($totalValue, 2) }}</td>
                        <td class="text-right">{{ number_format($runningBalance, 3) }}</td>
                    </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </x-card>

    <style>
        @media print {
            .no-print {
                display: none !important;
            }
        }
    </style>
</div>
