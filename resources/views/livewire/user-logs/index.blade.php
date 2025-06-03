<?php

use Illuminate\Support\Facades\Gate;
use Spatie\SimpleExcel\SimpleExcelWriter;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Volt\Component;
use Livewire\Attributes\Session;
use Livewire\WithPagination;
use Mary\Traits\Toast;
use App\Models\UserLog;

new class extends Component {
    use Toast, WithPagination;

    #[Session(key: 'userlog_per_page')]
    public int $perPage = 10;

    #[Session(key: 'userlog_name')]
    public string $name = '';

    #[Session(key: 'userlog_active')]
    public string $is_active = '';

    public int $filterCount = 0;
    public bool $drawer = false;
    public array $sortBy = ['column' => 'id', 'direction' => 'desc'];
    public array $activeList;

    public function mount(): void
    {
        Gate::authorize('view user logs');
        $this->updateFilterCount();

        $this->activeList = [
            ['id' => 'active', 'name' => 'Active'],
            ['id' => 'inactive', 'name' => 'Inactive']
        ];
    }

    public function headers(): array
    {
        return [
            ['key' => 'action', 'label' => 'Action'],
            ['key' => 'resource', 'label' => 'Resource'],
            ['key' => 'ref_id', 'label' => 'Ref. ID'],
            ['key' => 'user_name', 'label' => 'User'],
            ['key' => 'created_at', 'label' => 'Created At', 'class' => 'lg:w-[160px]', 'format' => ['date', 'd-M-y, H:i']],
            // ['key' => 'updated_at', 'label' => 'Updated At', 'class' => 'lg:w-[160px]', 'format' => ['date', 'd-M-y, H:i']],
        ];
    }

    public function userLogs(): LengthAwarePaginator
    {
        return UserLog::query()
        ->withAggregate('user','name')
        ->orderBy(...array_values($this->sortBy))
        ->filterLike('user.name', $this->name)
        ->active($this->is_active)
        ->paginate($this->perPage);
    }

    public function with(): array
    {
        return [
            'headers' => $this->headers(),
            'userLogs' => $this->userLogs(),
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
        $this->filterCount = $count;
    }

    public function delete(UserLog $userlog): void
    {
        Gate::authorize('delete user logs');
        $userlog->delete();
        $this->success('Log has been deleted.');
    }

    public function export()
    {
        Gate::authorize('export user logs');

        $userLogs = UserLog::with(['coaBuying','coaSelling'])->orderBy('id','asc')->get();
        $writer = SimpleExcelWriter::streamDownload('User logs.xlsx');
        foreach ( $userLogs->lazy() as $userLog ) {
            $writer->addRow([
                'id' => $userLog->id ?? '',
                'code' => $userLog->code ?? '',
                'name' => $userLog->name ?? '',
                'type' => $userLog->type ?? '',
                'coa_buying' => $userLog->coaBuying->code,
                'coa_selling' => $userLog->coaSelling->code,
                'is_active' => $userLog->is_active ?? '',
            ]);
        }
        return response()->streamDownload(function() use ($writer){
            $writer->close();
        }, 'User logs.xlsx');
    }
}; ?>

<div>
    {{-- HEADER --}}
    <div class="lg:top-[65px] lg:sticky z-10 bg-base-200 pb-0.5 pt-3">
    <x-header title="User logs" separator progress-indicator>
        <x-slot:actions>
            @can('export user logs')
            <x-button label="Export" wire:click="export" spinner="export" icon="o-arrow-down-tray" />
            @endcan
            <x-button label="Filters" @click="$wire.drawer = true" icon="o-funnel" badge="{{ $filterCount }}" />
        </x-slot:actions>
    </x-header>
    </div>

    {{-- TABLE --}}
    <x-card wire:loading.class="bg-slate-200/50 text-slate-400">
        <x-table
            :headers="$headers"
            :rows="$userLogs"
            :sort-by="$sortBy"
            with-pagination
            per-page="perPage"
            show-empty-text
            :link="route('user-logs.edit', ['userLog' => '[id]'])"
        >
            @scope('cell_action', $userLog)
            @if ($userLog->action == 'update')
            <x-badge value="Update" class="text-xs badge-success" />
            @elseif ($userLog->action == 'delete')
            <x-badge value="Delete" class="text-xs badge-error" />
            @elseif ($userLog->action == 'create')
            <x-badge value="Create" class="text-xs badge-primary" />
            @endif
            @endscope
            @scope('actions', $userLog)
            <div class="flex gap-1.5">
                @can('delete user logs')
                <x-button wire:click="delete({{ $userLog->id }})" spinner="delete({{ $userLog->id }})" wire:confirm="Are you sure you want to delete this row?" icon="o-trash" class="btn btn-sm" />
                @endcan
                @can('update user logs')
                <x-button link="{{ route('user-logs.edit', $userLog->id) }}" icon="o-pencil-square" class="btn btn-sm" />
                @endcan
            </div>
            @endscope
        </x-table>
    </x-card>

    {{-- FILTER DRAWER --}}
    <x-drawer wire:model="drawer" title="Filters" right separator with-close-button class="lg:w-1/3">
        <x-form wire:submit="search">
            <div class="grid gap-4">
                <x-input label="Resource" wire:model="resource" />
            </div>
            <x-slot:actions>
                <x-button label="Reset" icon="o-x-mark" wire:click="clear" spinner="clear" />
                <x-button label="Search" icon="o-magnifying-glass" spinner="search" type="submit" class="btn-primary" />
            </x-slot:actions>
        </x-form>
    </x-drawer>
</div>
