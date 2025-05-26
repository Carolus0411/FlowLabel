<?php

use Illuminate\Support\Facades\Gate;
use Spatie\SimpleExcel\SimpleExcelWriter;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Volt\Component;
use Livewire\Attributes\Session;
use Livewire\WithPagination;
use Mary\Traits\Toast;
use App\Models\Product;

new class extends Component {
    use Toast, WithPagination;

    #[Session(key: 'product_per_page')]
    public int $perPage = 10;

    #[Session(key: 'product_name')]
    public string $name = '';

    #[Session(key: 'product_active')]
    public string $is_active = '';

    public int $filterCount = 0;
    public bool $drawer = false;
    public array $sortBy = ['column' => 'id', 'direction' => 'desc'];

    public function mount(): void
    {
        Gate::authorize('view product');
        $this->updateFilterCount();
    }

    public function headers(): array
    {
        return [
            ['key' => 'name', 'label' => 'Name'],
            ['key' => 'category.name', 'label' => 'Category', 'sortable' => false],
            ['key' => 'brand.name', 'label' => 'Brand', 'sortable' => false],
            ['key' => 'is_active', 'label' => 'Active'],
            ['key' => 'is_featured', 'label' => 'Featured'],
            // ['key' => 'created_at', 'label' => 'Created At', 'class' => 'lg:w-[160px]', 'format' => ['date', 'd-M-y, H:i']],
            ['key' => 'updated_at', 'label' => 'Updated At', 'class' => 'lg:w-[160px]', 'format' => ['date', 'd-M-y, H:i']],
        ];
    }

    public function categories(): LengthAwarePaginator
    {
        return Product::query()
        ->with(['category','brand'])
        ->orderBy(...array_values($this->sortBy))
        ->filterLike('name', $this->name)
        ->filterWhere('is_active', $this->is_active)
        ->paginate($this->perPage);
    }

    public function with(): array
    {
        return [
            'headers' => $this->headers(),
            'categories' => $this->categories(),
        ];
    }

    public function updated($property): void
    {
        if (! is_array($property) && $property != "") {
            $this->resetPage();
            $this->updateFilterCount();
        }
    }

    public function search(): void
    {
        $data = $this->validate([
            'name' => 'nullable',
        ]);
    }

    public function clear(): void
    {
        $this->success('Filters cleared.');
        $this->reset();
        $this->resetPage();
        $this->updateFilterCount();
    }

    public function updateFilterCount(): void
    {
        $count = 0;
        if (!empty($this->name)) {
            $count++;
        }
        if (!empty($this->is_active)) {
            $count++;
        }
        if (!empty($this->is_featured)) {
            $count++;
        }
        $this->filterCount = $count;
    }

    public function delete(Product $product): void
    {
        Gate::authorize('delete product');

        $product->variants()->delete();
        $product->delete();

        $this->success('Product has been deleted.');
    }

    public function export()
    {
        Gate::authorize('export product');

        $product = Product::orderBy('id','asc');
        $writer = SimpleExcelWriter::streamDownload('Product.xlsx');
        foreach ( $product->lazy() as $product ) {
            $writer->addRow([
                'id' => $product->id ?? '',
                'name' => $product->name ?? '',
                'is_active' => $product->is_active ?? '',
            ]);
        }
        return response()->streamDownload(function() use ($writer){
            $writer->close();
        }, 'Product.xlsx');
    }
}; ?>

<div>
    {{-- HEADER --}}
    <x-header title="Product" separator progress-indicator>
        <x-slot:actions>
            @can('export product')
            <x-button label="Export" wire:click="export" spinner="export" icon="o-arrow-down-tray" />
            @endcan
            @can('import product')
            <x-button label="Import" link="{{ route('product.import') }}" icon="o-arrow-up-tray" />
            @endcan
            <x-button label="Filters" @click="$wire.drawer = true" icon="o-funnel" badge="{{ $filterCount }}" />
            @can('create product')
            <x-button label="Create" link="{{ route('product.create') }}" icon="o-plus" class="btn-primary" />
            @endcan
        </x-slot:actions>
    </x-header>

    {{-- TABLE --}}
    <x-card wire:loading.class="bg-slate-200/50 text-slate-400">
        <x-table :headers="$headers" :rows="$categories" :sort-by="$sortBy" with-pagination per-page="perPage" show-empty-text>
            @scope('cell_is_active', $product)
            @if ($product->is_active)
            <x-badge value="Active" class="text-xs uppercase badge-success badge-soft" />
            @else
            <x-badge value="Inactive" class="text-xs uppercase badge-error badge-soft" />
            @endif
            @endscope
            @scope('cell_is_featured', $product)
            @if ($product->is_featured)
            <x-badge value="Featured" class="text-xs uppercase badge-info badge-soft" />
            @endif
            @endscope
            @scope('actions', $product)
            <div class="flex gap-1.5">
                @can('delete product')
                <x-button wire:click="delete({{ $product->id }})" spinner="delete({{ $product->id }})" wire:confirm="Are you sure you want to delete this row?" icon="o-trash" class="btn btn-sm" />
                @endcan
                @can('update product')
                <x-button link="{{ route('product.edit', $product->id) }}" icon="o-pencil-square" class="btn btn-sm" />
                @endcan
            </div>
            @endscope
        </x-table>
    </x-card>

    {{-- FILTER DRAWER --}}
    <x-drawer wire:model="drawer" title="Filters" right separator with-close-button class="lg:w-1/3">
        <x-form wire:submit="search">
            <div class="grid gap-4">
                <x-input label="Name" wire:model="name" />
                <x-toggle label="Active" wire:model="is_active" right />
            </div>
            <x-slot:actions>
                <x-button label="Reset" icon="o-x-mark" wire:click="clear" spinner="clear" />
                <x-button label="Search" icon="o-magnifying-glass" spinner="search" type="submit" class="btn-primary" />
            </x-slot:actions>
        </x-form>
    </x-drawer>
</div>
