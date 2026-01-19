<?php

use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Volt\Component;
use Livewire\Attributes\Session;
use Livewire\WithPagination;
use Mary\Traits\Toast;
use App\Models\ServiceChargeGroup;

new class extends Component {
    use Toast, WithPagination;

    #[Session(key: 'servicechargegroup_per_page')]
    public int $perPage = 10;

    #[Session(key: 'servicechargegroup_name')]
    public string $name = '';

    #[Session(key: 'servicechargegroup_active')]
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
            ['key' => 'code', 'label' => 'Code'],
            ['key' => 'name', 'label' => 'Name'],
            ['key' => 'is_active', 'label' => 'Active'],
            ['key' => 'created_at', 'label' => 'Created At', 'class' => 'lg:w-[160px]', 'format' => ['date', 'd-M-y, H:i']],
            ['key' => 'updated_at', 'label' => 'Updated At', 'class' => 'lg:w-[160px]', 'format' => ['date', 'd-M-y, H:i']],
        ];
    }

    public function groups(): LengthAwarePaginator
    {
        return ServiceChargeGroup::query()
            ->orderBy(...array_values($this->sortBy))
            ->filterLike('name', $this->name)
            ->filterLike('code', $this->name)
            ->paginate($this->perPage);
    }

    public function with(): array
    {
        return [
            'headers' => $this->headers(),
            'groups' => $this->groups(),
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

    public function delete(ServiceChargeGroup $group): void
    {
        $group->delete();
        $this->success('Items Group has been deleted.');
    }
}; ?>

<div>
    <x-header title="Items Group" separator progress-indicator>
        <x-slot:actions>
            <x-button label="Filters" @click="$wire.drawer = true" icon="o-funnel" badge="{{ $filterCount }}" />
            <x-button label="Create" link="{{ route('items-master-group.create') }}" icon="o-plus" class="btn-primary" />
        </x-slot:actions>
    </x-header>

    <x-card wire:loading.class="bg-slate-200/50 text-slate-400">
        <x-table :headers="$headers" :rows="$groups" :sort-by="$sortBy" with-pagination per-page="perPage" show-empty-text>
            @scope('cell_is_active', $group)
            <x-active-badge :status="$group->is_active" />
            @endscope
            @scope('actions', $group)
            <div class="flex gap-1.5">
                <x-button wire:click="delete({{ $group->id }})" spinner="delete({{ $group->id }})" wire:confirm="Are you sure you want to delete this row?" icon="o-trash" class="btn btn-sm" />
                <x-button link="{{ route('service-charge-group.edit', $group->id) }}" icon="o-pencil-square" class="btn btn-sm" />
            </div>
            @endscope
        </x-table>
    </x-card>

    <x-search-drawer>
        <x-input label="Name or Code" wire:model="name" />
        <x-select label="Active" wire:model="is_active" :options="\App\Enums\ActiveStatus::toSelect()" placeholder="-- All --" />
    </x-search-drawer>
</div>
