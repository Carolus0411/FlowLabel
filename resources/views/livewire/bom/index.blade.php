<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\WithPagination;
use App\Models\BOM;
use Mary\Traits\Toast;

new #[Layout('components.layouts.app')] class extends Component {
    use WithPagination, Toast;

    public string $search = '';
    public bool $showFilters = false;
    public $filterActive = 'all';

    public function with(): array
    {
        $boms = BOM::query()
            ->with(['details.product', 'materials.material'])
            ->when($this->search, fn($q) => $q->where('bom_no', 'like', "%{$this->search}%")
                ->orWhere('description', 'like', "%{$this->search}%"))
            ->when($this->filterActive !== 'all', fn($q) => $q->where('is_active', $this->filterActive === 'active'))
            ->orderBy('bom_date', 'desc')
            ->orderBy('bom_no', 'desc')
            ->paginate(20);

        return [
            'boms' => $boms,
            'headers' => [
                ['key' => 'bom_no', 'label' => 'BOM No'],
                ['key' => 'bom_date', 'label' => 'Date'],
                ['key' => 'description', 'label' => 'Description'],
                ['key' => 'products_count', 'label' => 'Products'],
                ['key' => 'materials_count', 'label' => 'Materials'],
                ['key' => 'status', 'label' => 'Stock Status'],
                ['key' => 'is_active', 'label' => 'Active'],
                ['key' => 'actions', 'label' => 'Actions'],
            ]
        ];
    }

    public function delete(BOM $bom)
    {
        try {
            $bom->delete();
            $this->success('BOM deleted successfully');
        } catch (\Exception $e) {
            $this->error('Failed to delete BOM: ' . $e->getMessage());
        }
    }

    public function clearFilters()
    {
        $this->reset(['search', 'filterActive']);
    }
}; ?>

<div>
    <x-header title="Bill Of Material" separator progress-indicator>
        <x-slot:middle class="justify-end!">
            <x-input placeholder="Search..." wire:model.live.debounce="search" clearable icon="o-magnifying-glass" />
        </x-slot:middle>
        <x-slot:actions>
            <x-button label="Filters" icon="o-funnel" @click="$wire.showFilters = !$wire.showFilters" badge="{{ $filterActive !== 'all' ? '1' : '' }}" />
            <x-button label="New BOM" icon="o-plus" link="{{ route('bom.create') }}" class="btn-primary" />
        </x-slot:actions>
    </x-header>

    @if($showFilters)
        <x-card class="mb-4">
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
        <x-table :headers="$headers" :rows="$boms" with-pagination link="bom/{id}">
            @scope('cell_bom_date', $bom)
                {{ $bom->bom_date->format('d M Y') }}
            @endscope

            @scope('cell_description', $bom)
                <div class="max-w-xs truncate">{{ $bom->description ?? '-' }}</div>
            @endscope

            @scope('cell_products_count', $bom)
                <x-badge value="{{ $bom->details->count() }} items" class="badge-info" />
            @endscope

            @scope('cell_materials_count', $bom)
                <x-badge value="{{ $bom->materials->count() }} materials" class="badge-warning" />
            @endscope

            @scope('cell_status', $bom)
                @php
                    $insufficient = $bom->materials->where('is_sufficient', false)->count();
                @endphp
                @if($insufficient > 0)
                    <x-badge value="Insufficient ({{ $insufficient }})" class="badge-error" />
                @else
                    <x-badge value="Sufficient" class="badge-success" />
                @endif
            @endscope

            @scope('cell_is_active', $bom)
                @if($bom->is_active)
                    <x-badge value="Active" class="badge-success" />
                @else
                    <x-badge value="Inactive" class="badge-ghost" />
                @endif
            @endscope

            @scope('cell_actions', $bom)
                <div class="flex gap-2">
                    <x-button icon="o-eye" link="{{ route('bom.show', $bom->id) }}" class="btn-sm btn-ghost" />
                    <x-button icon="o-pencil" link="{{ route('bom.edit', $bom->id) }}" class="btn-sm btn-ghost" />
                    <x-button
                        icon="o-printer"
                        @click="window.open('{{ route('print.bom', $bom->id) }}', 'printWindow', 'width=1000,height=700,scrollbars=yes,resizable=yes')"
                        class="btn-sm btn-ghost"
                    />
                    <x-button icon="o-trash" wire:click="delete({{ $bom->id }})" wire:confirm="Are you sure?" class="btn-sm btn-ghost text-error" />
                </div>
            @endscope
        </x-table>
    </x-card>
</div>
