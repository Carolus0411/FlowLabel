<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use App\Models\Recipe;
use App\Models\ServiceCharge;
use App\Models\RecipeDetail;
use App\Models\Uom;
use Mary\Traits\Toast;

new #[Layout('components.layouts.app')] class extends Component {
    use Toast;

    public Recipe $recipe;
    public string $code = '';
    public string $name = '';
    public $product_id = '';
    public string $yield_qty = '';
    public string $description = '';
    public bool $is_active = true;

    public array $details = [];
    public int $detailIndex = 0;

    public function mount(Recipe $recipe)
    {
        $this->recipe = $recipe->load(['details.material', 'details.uom', 'product']);
        $this->code = $recipe->code;
        $this->name = $recipe->name;
        $this->product_id = $recipe->product_id;
        $this->yield_qty = $recipe->yield_qty;
        $this->description = $recipe->description ?? '';
        $this->is_active = $recipe->is_active;

        foreach ($recipe->details as $detail) {
            $this->details[] = [
                'index' => $this->detailIndex++,
                'id' => $detail->id,
                'material_id' => $detail->material_id,
                'qty' => $detail->qty,
                'uom_id' => $detail->uom_id,
                'notes' => $detail->notes,
            ];
        }
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
            'id' => null,
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
            'code' => 'required|unique:recipes,code,' . $this->recipe->id,
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
            $this->recipe->update([
                'code' => $this->code,
                'name' => $this->name,
                'product_id' => $this->product_id,
                'yield_qty' => $this->yield_qty,
                'description' => $this->description,
                'is_active' => $this->is_active,
            ]);

            // Delete removed details
            $keepIds = array_filter(array_column($this->details, 'id'));
            RecipeDetail::where('recipe_id', $this->recipe->id)
                ->whereNotIn('id', $keepIds)
                ->delete();

            // Update or create details
            foreach ($this->details as $detail) {
                if ($detail['id']) {
                    RecipeDetail::where('id', $detail['id'])->update([
                        'material_id' => $detail['material_id'],
                        'qty' => $detail['qty'],
                        'uom_id' => $detail['uom_id'] ?: null,
                        'notes' => $detail['notes'],
                    ]);
                } else {
                    RecipeDetail::create([
                        'recipe_id' => $this->recipe->id,
                        'material_id' => $detail['material_id'],
                        'qty' => $detail['qty'],
                        'uom_id' => $detail['uom_id'] ?: null,
                        'notes' => $detail['notes'],
                    ]);
                }
            }

            $this->success('Recipe updated successfully');
            return redirect()->route('recipe.index');
        } catch (\Exception $e) {
            $this->error('Failed to update recipe: ' . $e->getMessage());
        }
    }
}; ?>

<div>
    <x-header title="Edit Recipe: {{ $recipe->code }}" separator />

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
                <table class="table table-zebra">
                    <thead>
                        <tr>
                            <th class="w-5/12">Material</th>
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
                                    wire:model="details.{{ $index }}.material_id"
                                    :options="$this->materials"
                                    placeholder="Select material..."
                                    class="w-full"
                                    required
                                />
                            </td>
                            <td>
                                <x-input
                                    wire:model="details.{{ $index }}.qty"
                                    type="number"
                                    step="0.001"
                                    class="w-full"
                                    required
                                />
                            </td>
                            <td>
                                <x-select
                                    wire:model="details.{{ $index }}.uom_id"
                                    :options="$this->uoms"
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
            <x-button label="Update Recipe" type="submit" class="btn-primary" />
        </div>
    </x-form>
</div>
