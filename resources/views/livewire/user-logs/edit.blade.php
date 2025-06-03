<?php

use Illuminate\Support\Facades\Gate;
use Livewire\Volt\Component;
use Mary\Traits\Toast;
use App\Models\UserLog;

new class extends Component {
    use Toast;

    public UserLog $userLog;

    public $resource = '';
    public $action = '';
    public $user_name = '';
    public $date = '';
    public $data = '';

    public function mount(): void
    {
        Gate::authorize('update user logs');
        $this->fill($this->userLog);
        $this->user_name = $this->userLog->user->name;
        $this->date = $this->userLog->created_at->format('Y-m-d H:i:s');
    }
}; ?>

<div>
    <x-header title="View User logs" separator>
        <x-slot:actions>
            <x-button label="Back" link="{{ route('user-logs.index') }}" icon="o-arrow-uturn-left" />
        </x-slot:actions>
    </x-header>

    <x-card>
        <div class="space-y-4">
            <div class="space-y-4 lg:space-y-0 lg:grid grid-cols-4 gap-4">
                <x-input label="Resource" wire:model="resource" class="bg-base-200" readonly />
                <x-input label="Action" wire:model="action" class="bg-base-200" readonly />
                <x-input label="User" wire:model="user_name" class="bg-base-200" readonly />
                <x-input label="Date" wire:model="date" class="bg-base-200" readonly />
            </div>
            {{-- <div>
                @dump(json_decode($userLog->data, TRUE))
            </div> --}}

            <div>
                <div class="text-xs my-2 font-semibold">Data</div>
                <div class="text-xs overflow-auto border border-base-300 bg-base-200 px-4 py-2">
                    <pre>
@php print_r(json_decode($userLog->data)) @endphp
                    </pre>
                </div>
            </div>
        </div>
    </x-card>
</div>
