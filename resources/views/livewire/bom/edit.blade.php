<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use App\Models\BOM;
use App\Models\BOMDetail;
use App\Models\BOMMaterial;
use App\Models\ServiceCharge;
use App\Models\Recipe;
use App\Models\Uom;
use App\Models\InventoryLedger;
use Mary\Traits\Toast;

new #[Layout('components.layouts.app')] class extends Component {
    use Toast;

    public BOM $bom;

    public $bom_no = '';
    public $bom_date = '';
    public $description = '';
    public $is_active = true;

    public $details = [];
    public $detailIndex = 0;

    public $materials = [];

    public function mount(BOM $bom)
    {
        $this->bom = $bom->load(['details', 'materials']);

        $this->bom_no = $this->bom->bom_no;
        $this->bom_date = $this->bom->bom_date->format('Y-m-d');
        $this->description = $this->bom->description;
        $this->is_active = $this->bom->is_active;

        foreach ($this->bom->details as $detail) {
            $this->details[] = [
                'index' => $this->detailIndex++,
                'id' => $detail->id,
                'product_id' => $detail->product_id,
                'qty' => $detail->qty,
                'uom_id' => $detail->uom_id,
                'notes' => $detail->notes,
            ];
        }

        $this->calculateMaterials();
    }

    public function addDetail()
    {
        $this->details[] = [
            'index' => $this->detailIndex++,
            'id' => null,
            'product_id' => null,
            'qty' => 1,
            'uom_id' => null,
            'notes' => '',
        ];
    }

    public function removeDetail($index)
    {
        $this->details = array_values(array_filter($this->details, fn($d) => $d['index'] !== $index));
        $this->calculateMaterials();
    }

    public function updatedDetails()
    {
        $this->calculateMaterials();
    }

    public function calculateMaterials()
    {
        $materialAggregate = [];

        foreach ($this->details as $detail) {
            if (!$detail['product_id'] || !$detail['qty']) {
                continue;
            }

            // Find recipe for this product
            $recipe = Recipe::where('product_id', $detail['product_id'])
                ->where('is_active', true)
                ->with(['details.material', 'details.uom'])
                ->first();

            if (!$recipe) {
                continue;
            }

            // Calculate materials needed based on recipe
            foreach ($recipe->details as $recipeDetail) {
                $materialId = $recipeDetail->material_id;
                $requiredQtyPerUnit = $recipeDetail->qty;
                $totalRequired = ($detail['qty'] / $recipe->yield_qty) * $requiredQtyPerUnit;
                $uomId = $recipeDetail->uom_id;

                if (!isset($materialAggregate[$materialId])) {
                    $materialAggregate[$materialId] = [
                        'material_id' => $materialId,
                        'material_name' => $recipeDetail->material->name ?? '',
                        'required_qty' => 0,
                        'uom_id' => $uomId,
                        'uom_name' => $recipeDetail->uom->name ?? '',
                        'available_qty' => 0,
                        'is_sufficient' => false,
                    ];
                }

                $materialAggregate[$materialId]['required_qty'] += $totalRequired;
            }
        }

        // Check available stock for each material
        foreach ($materialAggregate as $materialId => &$material) {
            $inventory = InventoryLedger::where('service_charge_id', $materialId)
                ->sum('qty');

            $material['available_qty'] = $inventory;
            $material['is_sufficient'] = $inventory >= $material['required_qty'];
        }

        $this->materials = array_values($materialAggregate);
    }

    public function products()
    {
        return ServiceCharge::where('item_type_id', 5) // Finished Product items only
            ->where('is_active', true)
            ->whereHas('recipes') // Only products that have recipes
            ->orderBy('name')
            ->get()
            ->map(fn($item) => ['id' => $item->id, 'name' => $item->name])
            ->toArray();
    }

    public function uoms()
    {
        return Uom::orderBy('name')
            ->get()
            ->map(fn($uom) => ['id' => $uom->id, 'name' => $uom->name])
            ->toArray();
    }

    public function update()
    {
        $this->validate([
            'bom_no' => 'required|unique:boms,bom_no,' . $this->bom->id,
            'bom_date' => 'required|date',
            'details' => 'required|array|min:1',
            'details.*.product_id' => 'required|exists:service_charge,id',
            'details.*.qty' => 'required|numeric|min:0.01',
        ]);

        try {
            $this->bom->update([
                'bom_no' => $this->bom_no,
                'bom_date' => $this->bom_date,
                'description' => $this->description,
                'is_active' => $this->is_active,
            ]);

            // Delete old details and materials
            $this->bom->details()->delete();
            $this->bom->materials()->delete();

            // Create new details
            foreach ($this->details as $detail) {
                BOMDetail::create([
                    'bom_id' => $this->bom->id,
                    'product_id' => $detail['product_id'],
                    'qty' => $detail['qty'],
                    'uom_id' => $detail['uom_id'],
                    'notes' => $detail['notes'],
                ]);
            }

            // Create new materials
            foreach ($this->materials as $material) {
                BOMMaterial::create([
                    'bom_id' => $this->bom->id,
                    'material_id' => $material['material_id'],
                    'required_qty' => $material['required_qty'],
                    'uom_id' => $material['uom_id'],
                    'available_qty' => $material['available_qty'],
                    'is_sufficient' => $material['is_sufficient'],
                ]);
            }

            $this->success('BOM updated successfully');
            return redirect()->route('bom.show', $this->bom->id);
        } catch (\Exception $e) {
            $this->error('Failed to update BOM: ' . $e->getMessage());
        }
    }
}; ?>

<div>
    <x-header title="Edit Bill Of Material: {{ $bom->bom_no }}" separator />

    <x-form wire:submit="update">
        <x-card title="BOM Information">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <x-input label="BOM No" wire:model="bom_no" required />
                <x-input label="BOM Date" wire:model="bom_date" type="date" required />
                <x-toggle label="Active" wire:model="is_active" />
            </div>

            <div class="mt-4">
                <x-textarea label="Description" wire:model="description" rows="3" />
            </div>
        </x-card>

        <x-card title="Products to Produce" class="mt-4">
            <div class="overflow-x-auto">
                <table class="table table-zebra">
                    <thead>
                        <tr>
                            <th class="w-6/12">Product (Stock Item)</th>
                            <th class="w-2/12">Quantity</th>
                            <th class="w-2/12">UOM</th>
                            <th class="w-2/12">Notes</th>
                            <th class="w-1/12">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($details as $index => $detail)
                        <tr wire:key="detail-{{ $detail['index'] }}">
                            <td>
                                <x-select
                                    wire:model.live="details.{{ $index }}.product_id"
                                    :options="$this->products()"
                                    placeholder="Select product..."
                                    class="w-full"
                                    required
                                />
                            </td>
                            <td>
                                <x-input
                                    wire:model.blur="details.{{ $index }}.qty"
                                    type="number"
                                    step="0.01"
                                    class="w-full"
                                    required
                                />
                            </td>
                            <td>
                                <x-select
                                    wire:model="details.{{ $index }}.uom_id"
                                    :options="$this->uoms()"
                                    placeholder="Select UOM..."
                                    class="w-full"
                                />
                            </td>
                            <td>
                                <x-input wire:model="details.{{ $index }}.notes" class="w-full" />
                            </td>
                            <td>
                                <x-button
                                    icon="o-trash"
                                    wire:click="removeDetail({{ $detail['index'] }})"
                                    class="btn-sm btn-error btn-outline"
                                    type="button"
                                />
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>

                <x-button
                    label="Add Product"
                    icon="o-plus"
                    wire:click="addDetail"
                    class="btn-outline btn-sm mt-4"
                    type="button"
                />
            </div>
        </x-card>

        <x-card title="Required Materials (Aggregated from Recipes)" class="mt-4">
            @if(count($materials) > 0)
                <div class="overflow-x-auto">
                    <table class="table table-zebra">
                        <thead>
                            <tr>
                                <th class="w-4/12">Raw Material</th>
                                <th class="w-2/12 text-right">Required Qty</th>
                                <th class="w-2/12 text-right">Available Qty</th>
                                <th class="w-1/12">UOM</th>
                                <th class="w-2/12 text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($materials as $material)
                            <tr>
                                <td>{{ $material['material_name'] }}</td>
                                <td class="text-right font-semibold">{{ number_format($material['required_qty'], 2) }}</td>
                                <td class="text-right">{{ number_format($material['available_qty'], 2) }}</td>
                                <td>{{ $material['uom_name'] }}</td>
                                <td class="text-center">
                                    @if($material['is_sufficient'])
                                        <x-badge value="Sufficient" class="badge-success" />
                                    @else
                                        <x-badge value="Insufficient" class="badge-error" />
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center text-gray-500 py-8">
                    <p>Add products to see required materials</p>
                </div>
            @endif
        </x-card>

        <div class="flex justify-end gap-4 mt-4">
            <x-button label="Cancel" link="{{ route('bom.show', $bom->id) }}" />
            <x-button label="Update BOM" type="submit" class="btn-primary" />
        </div>
    </x-form>
</div>
