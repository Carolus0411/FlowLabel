<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use App\Models\Production;
use App\Models\ProductionDetail;
use App\Models\InventoryLedger;
use App\Models\ServiceCharge;
use Mary\Traits\Toast;
use Illuminate\Support\Facades\DB;

new #[Layout('components.layouts.app')] class extends Component {
    use Toast;

    public Production $production;
    public string $code = '';
    public string $date = '';
    public $bom_id = '';
    public $product_id = '';
    public $qty = 1;
    public $uom_id = '';
    public string $description = '';

    public array $materials = [];

    public function mount(Production $production)
    {
        $this->production = $production->load(['details.material', 'details.uom', 'bom', 'product', 'uom']);
        $this->code = $production->code;
        $this->date = $production->date->format('Y-m-d');
        $this->bom_id = $production->bom_id;
        $this->product_id = $production->product_id;
        $this->qty = $production->qty;
        $this->uom_id = $production->uom_id;
        $this->description = $production->description ?? '';

        foreach ($production->details as $detail) {
            // Get current stock
            $stock = InventoryLedger::where('service_charge_id', $detail->material_id)
                ->sum('qty');

            $this->materials[] = [
                'id' => $detail->id,
                'material_id' => $detail->material_id,
                'name' => $detail->material->name,
                'code' => $detail->material->code,
                'qty' => $detail->qty,
                'uom_id' => $detail->uom_id,
                'uom_name' => $detail->uom->name ?? '',
                'stock' => $stock,
                'notes' => $detail->notes,
            ];
        }
    }

    public function save()
    {
        if ($this->production->status === 'done') {
            $this->error('Cannot edit completed production');
            return;
        }

        $this->validate([
            'date' => 'required|date',
            'qty' => 'required|numeric|min:0.01',
            'materials.*.qty' => 'required|numeric|min:0',
        ]);

        try {
            $this->production->update([
                'date' => $this->date,
                'qty' => $this->qty,
                'description' => $this->description,
            ]);

            // Update details
            foreach ($this->materials as $material) {
                ProductionDetail::where('id', $material['id'])->update([
                    'qty' => $material['qty'],
                    'notes' => $material['notes'],
                ]);
            }

            $this->success('Production updated successfully');
        } catch (\Exception $e) {
            $this->error('Failed to update production: ' . $e->getMessage());
        }
    }

    public function finishProduction()
    {
        if ($this->production->status === 'done') {
            return;
        }

        // Validate stock
        foreach ($this->materials as $material) {
            if ($material['stock'] < $material['qty']) {
                $this->error("Insufficient stock for {$material['name']}. Required: {$material['qty']}, Available: {$material['stock']}");
                return;
            }
        }

        DB::beginTransaction();
        try {
            // 1. Deduct Raw Materials
            $totalCost = 0;
            foreach ($this->materials as $material) {
                if ($material['qty'] > 0) {
                    // Calculate cost (simple average or last price - simplified here as 0 or fetch from item)
                    // Ideally we should use FIFO or Average Costing.
                    // For now, let's assume we just track Qty.

                    InventoryLedger::create([
                        'date' => $this->date,
                        'service_charge_id' => $material['material_id'],
                        'qty' => -1 * $material['qty'],
                        'price' => 0, // TODO: Implement costing
                        'type' => 'out',
                        'reference_type' => Production::class,
                        'reference_id' => $this->production->id,
                        'transaction_source' => 'Production',
                        'reference_number' => $this->production->code,
                    ]);
                }
            }

            // 2. Add Finished Product
            InventoryLedger::create([
                'date' => $this->date,
                'service_charge_id' => $this->product_id,
                'qty' => $this->qty,
                'price' => 0, // TODO: Implement costing
                'type' => 'in',
                'reference_type' => Production::class,
                'reference_id' => $this->production->id,
                'transaction_source' => 'Production',
                'reference_number' => $this->production->code,
            ]);

            // 3. Update Status
            $this->production->update(['status' => 'done']);

            DB::commit();
            $this->success('Production finished successfully. Stock updated.');
            return redirect()->route('production.index');

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Failed to finish production: ' . $e->getMessage());
        }
    }
}; ?>

<div>
    <x-header title="Edit Production: {{ $code }}" separator>
        <x-slot:actions>
            <x-button label="Back" icon="o-arrow-left" link="{{ route('production.index') }}" />
            @if($production->status !== 'done')
                <x-button label="Finish Production" icon="o-check" wire:click="finishProduction" wire:confirm="Are you sure? This will update inventory." class="btn-success" />
            @endif
        </x-slot:actions>
    </x-header>

    <x-form wire:submit="save">
        <x-card title="Production Details">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-input label="Production No" wire:model="code" readonly />
                <x-datetime label="Date" wire:model="date" type="date" required :readonly="$production->status === 'done'" />

                <x-input label="BOM" value="{{ $production->bom->bom_no }}" readonly />
                <x-input label="Product" value="{{ $production->product->name }}" readonly />

                <x-input label="Quantity to Produce" wire:model="qty" type="number" step="0.01" required :readonly="$production->status === 'done'" />

                <div class="form-control w-full">
                    <label class="label">
                        <span class="label-text">Description</span>
                    </label>
                    <textarea wire:model="description" class="textarea textarea-bordered h-24" placeholder="Notes..." {{ $production->status === 'done' ? 'readonly' : '' }}></textarea>
                </div>
            </div>

            <div class="mt-4">
                <x-badge value="Status: {{ ucfirst($production->status) }}" class="{{ $production->status === 'done' ? 'badge-success' : 'badge-warning' }}" />
            </div>
        </x-card>

        <x-card title="Raw Materials Required" class="mt-4">
            <div class="overflow-x-auto">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Material</th>
                            <th class="text-right">Current Stock</th>
                            <th class="w-32">Qty Used</th>
                            <th>UOM</th>
                            <th>Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($materials as $index => $material)
                        <tr wire:key="mat-{{ $index }}">
                            <td>
                                <div class="font-bold">{{ $material['name'] }}</div>
                                <div class="text-xs text-gray-500">{{ $material['code'] }}</div>
                            </td>
                            <td class="text-right">
                                <span class="{{ $material['stock'] < $material['qty'] ? 'text-error font-bold' : 'text-success' }}">
                                    {{ number_format($material['stock'], 2) }}
                                </span>
                            </td>
                            <td>
                                <x-input
                                    wire:model="materials.{{ $index }}.qty"
                                    type="number"
                                    step="0.01"
                                    class="input-sm"
                                    :readonly="$production->status === 'done'"
                                />
                            </td>
                            <td>{{ $material['uom_name'] }}</td>
                            <td>
                                <x-input wire:model="materials.{{ $index }}.notes" class="input-sm" :readonly="$production->status === 'done'" />
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-card>

        @if($production->status !== 'done')
            <x-slot:actions>
                <x-button label="Save Changes" type="submit" class="btn-primary" />
            </x-slot:actions>
        @endif
    </x-form>
</div>
