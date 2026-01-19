<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use App\Models\Recipe;
use App\Models\ServiceCharge;
use App\Models\RecipeDetail;
use App\Models\Uom;
use Mary\Traits\Toast;
use Illuminate\Support\Str;

new #[Layout('components.layouts.app')] class extends Component {
    use Toast;

    public string $code = '';
    public string $name = '';
    public $product_id = '';
    public string $yield_qty = '1';
    public string $description = '';
    public bool $is_active = true;

    public array $details = [];
    public int $detailIndex = 0;

    public function mount()
    {
        $this->generateCode();
        $this->addDetail();
    }

    public function generateCode()
    {
        $lastRecipe = Recipe::orderBy('code', 'desc')->first();
        if ($lastRecipe) {
            $lastNumber = (int) substr($lastRecipe->code, 4);
            $newNumber = str_pad($lastNumber + 1, 5, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '00001';
        }
        $this->code = 'RCP-' . $newNumber;
    }

    public function getStockProductsProperty()
    {
        return ServiceCharge::query()
            ->with(['itemType'])
            ->where('item_type_id', 5) // Only Finished Product items
            ->orderBy('code')
            ->get()
            ->map(fn($item) => [
                'id' => $item->id,
                'name' => $item->code . ' - ' . $item->name,
            ]);
    }

    public function getMaterialsProperty()
    {
        // Get only Raw Material type items (item_type_id = 4)
        return ServiceCharge::query()
            ->with(['itemType'])
            ->where('item_type_id', 4) // Raw Material type
            ->orderBy('code')
            ->get()
            ->map(fn($item) => [
                'id' => $item->id,
                'name' => $item->code . ' - ' . $item->name,
            ]);
    }

    public function getUomsProperty()
    {
        return Uom::orderBy('name')
            ->get()
            ->map(fn($uom) => [
                'id' => $uom->id,
                'name' => $uom->name,
            ]);
    }

    public function addDetail()
    {
        $this->details[] = [
            'index' => $this->detailIndex++,
            'material_id' => '',
            'qty' => '',
            'uom_id' => '',
            'notes' => '',
        ];
    }

    public function removeDetail($index)
    {
        $this->details = array_filter($this->details, fn($detail) => $detail['index'] !== $index);
        $this->details = array_values($this->details);
    }

    public function save()
    {
        $this->validate([
            'code' => 'required|unique:recipes,code',
            'name' => 'required',
            'product_id' => 'required|exists:service_charge,id',
            'yield_qty' => 'required|numeric|min:0.01',
            'details.*.material_id' => 'required|exists:service_charge,id',
            'details.*.qty' => 'required|numeric|min:0.001',
            'details.*.uom_id' => 'nullable|exists:uom,id',
        ]);

        if (empty($this->details)) {
            $this->error('Please add at least one material');
            return;
        }

        try {
            $recipe = Recipe::create([
                'code' => $this->code,
                'name' => $this->name,
                'product_id' => $this->product_id,
                'yield_qty' => $this->yield_qty,
                'description' => $this->description,
                'is_active' => $this->is_active,
            ]);

            foreach ($this->details as $detail) {
                RecipeDetail::create([
                    'recipe_id' => $recipe->id,
                    'material_id' => $detail['material_id'],
                    'qty' => $detail['qty'],
                    'uom_id' => $detail['uom_id'] ?: null,
                    'notes' => $detail['notes'],
                ]);
            }

            $this->success('Recipe created successfully');
            return redirect()->route('recipe.index');
        } catch (\Exception $e) {
            $this->error('Failed to create recipe: ' . $e->getMessage());
        }
    }
}; ?>

<div>
    <x-header title="Create Recipe" separator />

    <x-form wire:submit="save">
        <x-card title="Recipe Information">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-input label="Code" wire:model="code" readonly />
                <x-input label="Recipe Name" wire:model="name" required />

                <x-select
                    label="Product (Stock Item)"
                    wire:model="product_id"
                    :options="$this->stockProducts"
                    placeholder="Select product..."
                    required
                />

                <x-input label="Yield Quantity" wire:model="yield_qty" type="number" step="0.01" required />
            </div>

            <div class="mt-4">
                <x-textarea label="Description" wire:model="description" rows="3" />
            </div>

            <div class="mt-4">
                <x-toggle label="Active" wire:model="is_active" />
            </div>
        </x-card>

        <x-card title="Materials (Raw Materials)" class="mt-4">
            <div class="overflow-x-auto">
                <table class="table">
                    <thead>
                        <tr>
                            <th class="w-1/3">Raw Material <span class="text-error">*</span></th>
                            <th class="w-32">Quantity <span class="text-error">*</span></th>
                            <th class="w-32">UOM</th>
                            <th>Notes</th>
                            <th class="w-10"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($details as $index => $detail)
                        <tr wire:key="detail-{{ $detail['index'] }}" class="align-top">
                            <td>
                                <x-select
                                    wire:model="details.{{ $index }}.material_id"
                                    :options="$this->materials"
                                    placeholder="Select raw material..."
                                    class="w-full"
                                />
                            </td>
                            <td>
                                <x-input
                                    wire:model="details.{{ $index }}.qty"
                                    type="number"
                                    step="0.001"
                                    class="w-full"
                                />
                            </td>
                            <td>
                                <x-select
                                    wire:model="details.{{ $index }}.uom_id"
                                    :options="$this->uoms"
                                    placeholder="Select..."
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
                                    class="btn-sm btn-ghost text-error"
                                    type="button"
                                />
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                <x-button
                    label="Add Raw Material"
                    icon="o-plus"
                    wire:click="addDetail"
                    class="btn-outline btn-sm"
                    type="button"
                />
            </div>
        </x-card>

        <div class="flex justify-end gap-4 mt-4">
            <x-button label="Cancel" link="{{ route('recipe.index') }}" />
            <x-button label="Save Recipe" type="submit" class="btn-primary" />
        </div>
    </x-form>
</div>
