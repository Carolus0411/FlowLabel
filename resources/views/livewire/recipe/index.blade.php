<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\WithPagination;
use App\Models\Recipe;
use Mary\Traits\Toast;

new #[Layout('components.layouts.app')] class extends Component {
    use WithPagination, Toast;

    public string $search = '';
    public bool $showFilters = false;
    public $filterActive = 'all';

    public function with(): array
    {
        $recipes = Recipe::query()
            ->with(['product.itemType', 'details.material', 'details.uom'])
            ->when($this->search, fn($q) => $q->where('code', 'like', "%{$this->search}%")
                ->orWhere('name', 'like', "%{$this->search}%")
                ->orWhereHas('product', fn($q) => $q->where('name', 'like', "%{$this->search}%")))
            ->when($this->filterActive !== 'all', fn($q) => $q->where('is_active', $this->filterActive === 'active'))
            ->orderBy('code')
            ->paginate(20);

        return [
            'recipes' => $recipes,
            'headers' => [
                ['key' => 'code', 'label' => 'Code'],
                ['key' => 'name', 'label' => 'Recipe Name'],
                ['key' => 'product', 'label' => 'Product'],
                ['key' => 'yield_qty', 'label' => 'Yield Qty'],
                ['key' => 'materials_count', 'label' => 'Raw Materials'],
                ['key' => 'is_active', 'label' => 'Status'],
                ['key' => 'actions', 'label' => 'Actions'],
            ]
        ];
    }

    public function delete(Recipe $recipe)
    {
        try {
            $recipe->delete();
            $this->success('Recipe deleted successfully');
        } catch (\Exception $e) {
            $this->error('Failed to delete recipe: ' . $e->getMessage());
        }
    }

    public function toggleActive(Recipe $recipe)
    {
        $recipe->update(['is_active' => !$recipe->is_active]);
        $this->success('Recipe status updated');
    }

    public function clearFilters()
    {
        $this->reset(['search', 'filterActive']);
    }
}; ?>

<div>
    <x-header title="Recipe" separator progress-indicator>
        <x-slot:middle class="!justify-end">
            <x-input placeholder="Search..." wire:model.live.debounce="search" clearable icon="o-magnifying-glass" />
        </x-slot:middle>
        <x-slot:actions>
            <x-button label="Filters" icon="o-funnel" @click="$wire.showFilters = !$wire.showFilters" badge="{{ $filterActive !== 'all' ? '1' : '' }}" />
            <x-button label="New Recipe" icon="o-plus" link="{{ route('recipe.create') }}" class="btn-primary" />
        </x-slot:actions>
    </x-header>

    @if($showFilters)
        <x-card class="mb-4 bg-base-200">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <x-select
                    label="Active Status"
                    wire:model.live="filterActive"
                    :options="[
                        ['id' => 'all', 'name' => 'All'],
                        ['id' => 'active', 'name' => 'Active Only'],
                        ['id' => 'inactive', 'name' => 'Inactive Only']
                    ]"
                    option-label="name"
                    option-value="id"
                />
            </div>
            <x-slot:actions>
                <x-button label="Clear" icon="o-x-mark" wire:click="clearFilters" />
            </x-slot:actions>
        </x-card>
    @endif

    <x-card>
        <x-table :headers="$headers" :rows="$recipes" with-pagination link="recipe/{id}/edit">
            @scope('cell_code', $recipe)
                <span class="font-bold">{{ $recipe->code }}</span>
            @endscope

            @scope('cell_product', $recipe)
                <div>
                    <div class="font-semibold">{{ $recipe->product->name }}</div>
                    <div class="text-xs text-gray-500">{{ $recipe->product->code }}</div>
                </div>
            @endscope

            @scope('cell_yield_qty', $recipe)
                <div class="text-right font-mono">{{ number_format($recipe->yield_qty, 2) }}</div>
            @endscope

            @scope('cell_materials_count', $recipe)
                <div class="flex items-center gap-2">
                    <x-badge value="{{ $recipe->details->count() }}" class="badge-info" />

                    @if($recipe->details->count() > 0)
                        <x-dropdown>
                            <x-slot:trigger>
                                <x-button icon="o-eye" class="btn-xs btn-ghost" />
                            </x-slot:trigger>
                            @foreach($recipe->details as $detail)
                                <x-menu-item
                                    title="{{ $detail->material->name }}"
                                    subtitle="{{ number_format($detail->qty, 2) }} {{ $detail->uom ? $detail->uom->name : '' }}"
                                />
                            @endforeach
                        </x-dropdown>
                    @endif
                </div>
            @endscope

            @scope('cell_is_active', $recipe)
                @if($recipe->is_active)
                    <x-badge value="Active" class="badge-success" />
                @else
                    <x-badge value="Inactive" class="badge-ghost" />
                @endif
            @endscope

            @scope('cell_actions', $recipe)
                <div class="flex gap-1" onclick="event.stopPropagation()">
                    <x-button icon="o-pencil" link="{{ route('recipe.edit', $recipe) }}" class="btn-sm btn-ghost" />
                    <x-button
                        icon="o-printer"
                        @click="window.open('{{ route('print.recipe', $recipe->id) }}', 'printWindow', 'width=1000,height=700,scrollbars=yes,resizable=yes')"
                        class="btn-sm btn-ghost"
                        tooltip="Print"
                    />
                    <x-button
                        icon="o-trash"
                        wire:click="delete({{ $recipe->id }})"
                        wire:confirm="Are you sure?"
                        class="btn-sm btn-ghost text-error"
                    />
                    <x-button
                        :icon="$recipe->is_active ? 'o-x-circle' : 'o-check-circle'"
                        wire:click="toggleActive({{ $recipe->id }})"
                        class="btn-sm btn-ghost"
                        :tooltip="$recipe->is_active ? 'Deactivate' : 'Activate'"
                    />
                </div>
            @endscope
        </x-table>
    </x-card>
</div>
