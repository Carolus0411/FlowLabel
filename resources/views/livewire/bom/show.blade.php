<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use App\Models\BOM;
use Mary\Traits\Toast;

new #[Layout('components.layouts.app')] class extends Component {
    use Toast;

    public BOM $bom;

    public function mount(BOM $bom)
    {
        $this->bom = $bom->load([
            'details.product.itemType',
            'details.uom',
            'materials.material',
            'materials.uom'
        ]);
    }

    public function with(): array
    {
        return [
            'productHeaders' => [
                ['key' => 'product', 'label' => 'Product'],
                ['key' => 'qty', 'label' => 'Quantity'],
                ['key' => 'uom', 'label' => 'UOM'],
                ['key' => 'notes', 'label' => 'Notes'],
            ],
            'materialHeaders' => [
                ['key' => 'material', 'label' => 'Raw Material'],
                ['key' => 'required_qty', 'label' => 'Required Qty'],
                ['key' => 'available_qty', 'label' => 'Available Qty'],
                ['key' => 'uom', 'label' => 'UOM'],
                ['key' => 'status', 'label' => 'Status'],
            ]
        ];
    }
}; ?>

<div>
    <x-header title="BOM Detail: {{ $bom->bom_no }}" separator>
        <x-slot:actions>
            <x-button label="Edit" icon="o-pencil" link="{{ route('bom.edit', $bom->id) }}" class="btn-primary" />
            <x-button label="Back" icon="o-arrow-left" link="{{ route('bom.index') }}" />
        </x-slot:actions>
    </x-header>

    <x-card title="BOM Information">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="text-sm font-semibold text-gray-600">BOM No</label>
                <p class="text-lg">{{ $bom->bom_no }}</p>
            </div>
            <div>
                <label class="text-sm font-semibold text-gray-600">Date</label>
                <p class="text-lg">{{ $bom->bom_date->format('d M Y') }}</p>
            </div>
            <div>
                <label class="text-sm font-semibold text-gray-600">Status</label>
                <div class="mt-1">
                    @if($bom->is_active)
                        <x-badge value="Active" class="badge-success" />
                    @else
                        <x-badge value="Inactive" class="badge-ghost" />
                    @endif
                </div>
            </div>
            <div>
                <label class="text-sm font-semibold text-gray-600">Stock Status</label>
                <div class="mt-1">
                    @php
                        $insufficient = $bom->materials->where('is_sufficient', false)->count();
                    @endphp
                    @if($insufficient > 0)
                        <x-badge value="Insufficient ({{ $insufficient }} materials)" class="badge-error" />
                    @else
                        <x-badge value="All Sufficient" class="badge-success" />
                    @endif
                </div>
            </div>
        </div>

        @if($bom->description)
            <div class="mt-4">
                <label class="text-sm font-semibold text-gray-600">Description</label>
                <p class="text-base mt-1">{{ $bom->description }}</p>
            </div>
        @endif
    </x-card>

    <x-card title="Products to Produce" class="mt-4">
        <x-table :headers="$productHeaders" :rows="$bom->details">
            @scope('cell_product', $detail)
                <div>
                    <div class="font-semibold">{{ $detail->product->name }}</div>
                    <div class="text-sm text-gray-500">{{ $detail->product->itemType->name ?? '' }}</div>
                </div>
            @endscope

            @scope('cell_qty', $detail)
                <span class="font-semibold">{{ number_format($detail->qty, 2) }}</span>
            @endscope

            @scope('cell_uom', $detail)
                {{ $detail->uom->name ?? '-' }}
            @endscope

            @scope('cell_notes', $detail)
                {{ $detail->notes ?? '-' }}
            @endscope
        </x-table>
    </x-card>

    <x-card title="Required Raw Materials" class="mt-4">
        <x-table :headers="$materialHeaders" :rows="$bom->materials">
            @scope('cell_material', $material)
                <div>
                    <div class="font-semibold">{{ $material->material->name }}</div>
                    <div class="text-sm text-gray-500">{{ $material->material->code ?? '' }}</div>
                </div>
            @endscope

            @scope('cell_required_qty', $material)
                <div class="text-right">
                    <span class="font-semibold text-blue-600">{{ number_format($material->required_qty, 2) }}</span>
                </div>
            @endscope

            @scope('cell_available_qty', $material)
                <div class="text-right">
                    <span class="{{ $material->is_sufficient ? 'text-green-600' : 'text-red-600' }} font-semibold">
                        {{ number_format($material->available_qty, 2) }}
                    </span>
                </div>
            @endscope

            @scope('cell_uom', $material)
                {{ $material->uom->name ?? '-' }}
            @endscope

            @scope('cell_status', $material)
                <div class="text-center">
                    @if($material->is_sufficient)
                        <x-badge value="✓ Sufficient" class="badge-success" />
                    @else
                        @php
                            $shortage = $material->required_qty - $material->available_qty;
                        @endphp
                        <x-badge value="✗ Short {{ number_format($shortage, 2) }}" class="badge-error" />
                    @endif
                </div>
            @endscope
        </x-table>

        <div class="mt-6 p-4 rounded-lg">
            <h3 class="font-bold text-lg mb-3">Summary</h3>
            <div class="grid grid-cols-3 gap-4">
                <div class="text-center p-3 bg-blue-100 rounded shadow-sm">
                    <div class="text-2xl font-bold text-gray-800">{{ $bom->details->count() }}</div>
                    <div class="text-sm text-gray-800 font-medium">Products to Produce</div>
                </div>
                <div class="text-center p-3 bg-blue-100 rounded shadow-sm">
                    <div class="text-2xl font-bold text-gray-800">{{ $bom->materials->where('is_sufficient', true)->count() }}</div>
                    <div class="text-sm text-gray-800 font-medium">Sufficient Materials</div>
                </div>
                <div class="text-center p-3 bg-blue-100 rounded shadow-sm">
                    <div class="text-2xl font-bold text-gray-800">{{ $bom->materials->where('is_sufficient', false)->count() }}</div>
                    <div class="text-sm text-gray-800 font-medium">Insufficient Materials</div>
                </div>
            </div>
        </div>
    </x-card>
</div>
