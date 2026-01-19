<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use App\Models\Production;
use App\Models\ProductionDetail;
use App\Models\BOM;
use App\Models\Recipe;
use App\Models\ServiceCharge;
use App\Models\Uom;
use App\Models\InventoryLedger;
use Mary\Traits\Toast;

new #[Layout('components.layouts.app')] class extends Component {
    use Toast;

    public string $code = '';
    public string $date = '';
    public $bom_id = '';
    public $product_id = '';
    public $qty = 1;
    public $uom_id = '';
    public string $description = '';

    public array $materials = [];

    // Options
    public $boms = [];
    public $products = [];

    public function mount()
    {
        $this->code = Production::generateCode();
        $this->date = date('Y-m-d');
        $this->boms = BOM::where('is_active', true)->orderBy('bom_no', 'desc')->get();
    }

    public function updatedBomId($value)
    {
        $this->product_id = '';
        $this->products = [];
        $this->materials = [];

        if ($value) {
            $bom = BOM::with('details.product')->find($value);
            if ($bom) {
                $this->products = $bom->details->map(fn($detail) => [
                    'id' => $detail->product_id,
                    'name' => $detail->product->name,
                    'uom_id' => $detail->uom_id,
                    'bom_qty' => $detail->qty // Keep track of BOM qty for ratio
                ]);

                // Auto select if only one product
                if (count($this->products) === 1) {
                    $this->product_id = $this->products[0]['id'];
                    $this->updatedProductId($this->product_id);
                }
            }
        }
    }

    public function updatedProductId($value)
    {
        $selectedProduct = collect($this->products)->firstWhere('id', $value);
        if ($selectedProduct) {
            $this->qty = $selectedProduct['bom_qty'];
        }
        $this->calculateMaterials();
    }

    public function updatedQty()
    {
        $this->calculateMaterials();
    }

    public function calculateMaterials()
    {
        if (!$this->bom_id || !$this->product_id || !$this->qty) {
            return;
        }

        // Get BOM Detail for UOM
        $bom = BOM::with('details')->find($this->bom_id);
        if (!$bom) return;

        $bomDetail = $bom->details->where('product_id', $this->product_id)->first();
        if ($bomDetail) {
            $this->uom_id = $bomDetail->uom_id;
        }

        // Get Recipe for the product
        $recipe = Recipe::with(['details.material', 'details.uom'])
            ->where('product_id', $this->product_id)
            ->where('is_active', true)
            ->first();

        $this->materials = [];

        if ($recipe && $recipe->yield_qty > 0) {
            // Ratio = Target Qty / Recipe Yield Qty
            $ratio = $this->qty / $recipe->yield_qty;

            foreach ($recipe->details as $detail) {
                // Get current stock
                $stock = InventoryLedger::where('service_charge_id', $detail->material_id)
                    ->sum('qty');

                $requiredQty = $detail->qty * $ratio;

                $this->materials[] = [
                    'material_id' => $detail->material_id,
                    'name' => $detail->material->name,
                    'code' => $detail->material->code,
                    'qty' => round($requiredQty, 2),
                    'uom_id' => $detail->uom_id,
                    'uom_name' => $detail->uom->name ?? '',
                    'stock' => $stock,
                    'notes' => '',
                ];
            }
        }
    }

    public function save()
    {
        $this->validate([
            'code' => 'required|unique:productions,code',
            'date' => 'required|date',
            'bom_id' => 'required',
            'product_id' => 'required',
            'qty' => 'required|numeric|min:0.01',
            'materials.*.qty' => 'required|numeric|min:0',
        ]);

        try {
            $production = Production::create([
                'code' => $this->code,
                'date' => $this->date,
                'bom_id' => $this->bom_id,
                'product_id' => $this->product_id,
                'qty' => $this->qty,
                'uom_id' => $this->uom_id,
                'status' => 'draft',
                'description' => $this->description,
            ]);

            foreach ($this->materials as $material) {
                ProductionDetail::create([
                    'production_id' => $production->id,
                    'material_id' => $material['material_id'],
                    'qty' => $material['qty'],
                    'uom_id' => $material['uom_id'],
                    'notes' => $material['notes'],
                ]);
            }

            $this->success('Production created successfully');
            return redirect()->route('production.edit', $production);
        } catch (\Exception $e) {
            $this->error('Failed to create production: ' . $e->getMessage());
        }
    }
}; ?>

<div>
    <x-header title="New Production" separator />

    <x-form wire:submit="save">
        <x-card title="Production Details">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-input label="Production No" wire:model="code" readonly />
                <x-datetime label="Date" wire:model="date" type="date" required />

                <x-select
                    label="BOM"
                    wire:model.live="bom_id"
                    :options="$boms"
                    option-label="bom_no"
                    option-value="id"
                    placeholder="Select BOM..."
                    required
                />

                <x-select
                    label="Product to Produce"
                    wire:model.live="product_id"
                    :options="$products"
                    option-label="name"
                    option-value="id"
                    placeholder="Select Product..."
                    required
                    :disabled="empty($products)"
                />

                <x-input label="Quantity to Produce" wire:model.live.debounce.500ms="qty" type="number" step="0.01" required />

                <div class="form-control w-full">
                    <label class="label">
                        <span class="label-text">Description</span>
                    </label>
                    <textarea wire:model="description" class="textarea textarea-bordered h-24" placeholder="Notes..."></textarea>
                </div>
            </div>
        </x-card>

        @if(!empty($materials))
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
                                />
                            </td>
                            <td>{{ $material['uom_name'] }}</td>
                            <td>
                                <x-input wire:model="materials.{{ $index }}.notes" class="input-sm" />
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-card>
        @endif

        <x-slot:actions>
            <x-button label="Cancel" link="{{ route('production.index') }}" />
            <x-button label="Save Draft" type="submit" class="btn-primary" />
        </x-slot:actions>
    </x-form>
</div>
