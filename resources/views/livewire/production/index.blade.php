<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\WithPagination;
use App\Models\Production;
use Mary\Traits\Toast;

new #[Layout('components.layouts.app')] class extends Component {
    use WithPagination, Toast;

    public string $search = '';

    public function with(): array
    {
        $productions = Production::query()
            ->with(['bom', 'product', 'uom'])
            ->when($this->search, fn($q) => $q->where('code', 'like', "%{$this->search}%")
                ->orWhereHas('product', fn($q) => $q->where('name', 'like', "%{$this->search}%"))
                ->orWhereHas('bom', fn($q) => $q->where('bom_no', 'like', "%{$this->search}%")))
            ->orderBy('code', 'desc')
            ->paginate(20);

        return [
            'productions' => $productions,
            'headers' => [
                ['key' => 'code', 'label' => 'Production No'],
                ['key' => 'date', 'label' => 'Date'],
                ['key' => 'bom.bom_no', 'label' => 'BOM No'],
                ['key' => 'product.name', 'label' => 'Product'],
                ['key' => 'qty', 'label' => 'Qty'],
                ['key' => 'status', 'label' => 'Status'],
                ['key' => 'actions', 'label' => 'Actions'],
            ]
        ];
    }

    public function delete(Production $production)
    {
        if ($production->status === 'done') {
            $this->error('Cannot delete completed production');
            return;
        }

        try {
            $production->delete();
            $this->success('Production deleted successfully');
        } catch (\Exception $e) {
            $this->error('Failed to delete production: ' . $e->getMessage());
        }
    }
}; ?>

<div>
    <x-header title="Production" separator progress-indicator>
        <x-slot:middle class="!justify-end">
            <x-input placeholder="Search..." wire:model.live.debounce="search" clearable icon="o-magnifying-glass" />
        </x-slot:middle>
        <x-slot:actions>
            <x-button label="New Production" icon="o-plus" link="{{ route('production.create') }}" class="btn-primary" />
        </x-slot:actions>
    </x-header>

    <x-card>
        <x-table :headers="$headers" :rows="$productions" with-pagination link="production/{id}/edit">
            @scope('cell_code', $production)
                <span class="font-bold">{{ $production->code }}</span>
            @endscope

            @scope('cell_date', $production)
                {{ $production->date->format('d M Y') }}
            @endscope

            @scope('cell_qty', $production)
                <div class="text-right font-mono">
                    {{ number_format($production->qty, 2) }} {{ $production->uom->name ?? '' }}
                </div>
            @endscope

            @scope('cell_status', $production)
                @if($production->status === 'done')
                    <x-badge value="Completed" class="badge-success" />
                @else
                    <x-badge value="Draft" class="badge-warning" />
                @endif
            @endscope

            @scope('cell_actions', $production)
                <div class="flex gap-1" onclick="event.stopPropagation()">
                    <x-button icon="o-pencil" link="{{ route('production.edit', $production) }}" class="btn-sm btn-ghost" />
                    <x-button
                        icon="o-printer"
                        @click="window.open('{{ route('print.production', $production->id) }}', 'printWindow', 'width=1000,height=700,scrollbars=yes,resizable=yes')"
                        class="btn-sm btn-ghost"
                    />
                    @if($production->status !== 'done')
                        <x-button
                            icon="o-trash"
                            wire:click="delete({{ $production->id }})"
                            wire:confirm="Are you sure?"
                            class="btn-sm btn-ghost text-error"
                        />
                    @endif
                </div>
            @endscope
        </x-table>
    </x-card>
</div>
