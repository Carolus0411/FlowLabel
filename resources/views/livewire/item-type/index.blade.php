<?php

use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Volt\Component;
use Livewire\Attributes\Session;
use Livewire\WithPagination;
use Mary\Traits\Toast;
use App\Models\ItemType;

new class extends Component {
    use Toast, WithPagination;

    #[Session(key: 'itemtype_per_page')]
    public int $perPage = 10;

    #[Session(key: 'itemtype_name')]
    public string $name = '';

    #[Session(key: 'itemtype_active')]
    public string $is_active = '';

    public int $filterCount = 0;
    public bool $drawer = false;
    public array $sortBy = ['column' => 'id', 'direction' => 'asc'];

    public function mount(): void
    {
        $this->updateFilterCount();
    }

    public function headers(): array
    {
        return [
            ['key' => 'name', 'label' => 'Name'],
            ['key' => 'is_stock', 'label' => 'Is Stock'],
            ['key' => 'is_active', 'label' => 'Active'],
            ['key' => 'created_at', 'label' => 'Created At', 'class' => 'lg:w-[160px]', 'format' => ['date', 'd-M-y, H:i']],
            ['key' => 'updated_at', 'label' => 'Updated At', 'class' => 'lg:w-[160px]', 'format' => ['date', 'd-M-y, H:i']],
        ];
    }

    public function types(): LengthAwarePaginator
    {
        return ItemType::query()
            ->orderBy(...array_values($this->sortBy))
            ->filterLike('name', $this->name)
            ->when($this->is_active, fn($q) => $q->where('is_active', $this->is_active == 'true'))
            ->paginate($this->perPage);
    }

    public function with(): array
    {
        return [
            'headers' => $this->headers(),
            'types' => $this->types(),
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
        if (!empty($this->name)) $count++;
        if (!empty($this->is_active)) $count++;
        $this->filterCount = $count;
    }

    public function delete(ItemType $type): void
    {
        $type->delete();
        $this->success('Item Type has been deleted.');
    }
}; ?>

<div>
    <x-header title="Item Type" separator progress-indicator>
        <x-slot:actions>
            <x-button label="Filters" @click="$wire.drawer = true" icon="o-funnel" badge="{{ $filterCount }}" />
            <x-button label="Create" link="{{ route('item-type.create') }}" icon="o-plus" class="btn-primary" />
        </x-slot:actions>
    </x-header>

    <x-card>
        <x-table :headers="$headers" :rows="$types" :sort-by="$sortBy" with-pagination link="item-type/{id}/edit">
            @scope('cell_is_stock', $type)
                @if($type->is_stock)
                    <x-icon name="o-check-circle" class="text-green-500" />
                @else
                    <x-icon name="o-x-circle" class="text-red-500" />
                @endif
            @endscope
            @scope('cell_is_active', $type)
                @if($type->is_active)
                    <x-icon name="o-check-circle" class="text-green-500" />
                @else
                    <x-icon name="o-x-circle" class="text-red-500" />
                @endif
            @endscope
            @scope('actions', $type)
            <x-button icon="o-trash" wire:click="delete({{ $type->id }})" wire:confirm="Are you sure?" spinner class="btn-ghost btn-sm text-red-500" />
            @endscope
        </x-table>
    </x-card>

    <x-drawer wire:model="drawer" title="Filters" right separator with-close-button class="lg:w-1/3">
        <div class="grid gap-5">
            <x-input placeholder="Search..." wire:model.live.debounce="name" icon="o-magnifying-glass" @keydown.enter="$wire.drawer = false" />
            <x-select placeholder="Active Status" wire:model.live="is_active" :options="[['id' => 'true', 'name' => 'Active'], ['id' => 'false', 'name' => 'Inactive']]" icon="o-check" />
        </div>
        <x-slot:actions>
            <x-button label="Reset" icon="o-x-mark" wire:click="clear" spinner />
            <x-button label="Done" icon="o-check" class="btn-primary" @click="$wire.drawer = false" />
        </x-slot:actions>
    </x-drawer>
</div>
