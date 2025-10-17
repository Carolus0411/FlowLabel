<?php

use Illuminate\Support\Facades\Gate;
use Livewire\Volt\Component;
use Mary\Traits\Toast;
use App\Helpers\Cast;
use App\Helpers\Code;
use App\Models\Request;

new class extends Component {
    use Toast;

    public Request $request;

    public $code = '';
    public $type = '';
    public $description = '';
    public $requestable_id = '';
    public $requestable_type = '';

    public $open = true;
    public $closeConfirm = false;

    public function mount(): void
    {
        Gate::authorize('update request');
        $this->fill($this->request);
    }

    public function with(): array
    {
        $this->open = $this->request->status == 'open';

        return [
            'resources' => [
                ['id' => 'App\Models\CashIn', 'name' => 'Cash In'],
                ['id' => 'App\Models\CashOut', 'name' => 'Cash Out'],
                ['id' => 'App\Models\SalesInvoice', 'name' => 'Sales Invoice'],
                ['id' => 'App\Models\SalesSettlement', 'name' => 'Sales Settlement'],
                ['id' => 'App\Models\Journal', 'name' => 'Journal'],
            ],
        ];
    }

    public function save($close = false): void
    {
        $this->closeConfirm = false;

        $data = $this->validate([
            'type' => 'required',
            'description' => 'required',
            'requestable_id' => ['required', new \App\Rules\ResourceCheck($this->requestable_type)],
            'requestable_type' => 'required',
        ]);

        $this->request->update($data);

        // if ($close) {
        //     $this->close();
        // }

        $this->success('Request successfully updated.', redirectTo: route('request.index'));
    }

    public function delete(Request $request): void
    {
        Gate::authorize('delete request');
        $request->delete();
        $this->success('Request successfully deleted.', redirectTo: route('request.index'));
    }

    public function approve(): void
    {
        Gate::authorize('close request');

        // \App\Jobs\RequestApprove::dispatchSync($this->request);
    }

    public function reject(): void
    {
        Gate::authorize('close request');

        // \App\Jobs\RequestApprove::dispatchSync($this->request);
    }

}; ?>

<div>
    <div class="lg:top-[65px] lg:sticky z-10 bg-base-200 pb-0 pt-3">
        <x-header separator>
            <x-slot:title>
                <div class="flex items-center-safe gap-4">
                    <span>Update Request</span>
                    <x-request-badge :status="$request->status" class="uppercase !text-sm" />
                </div>
            </x-slot:title>
            <x-slot:actions>
                <x-button label="Back" link="{{ route('request.index') }}" icon="o-arrow-uturn-left" class="btn-soft" />
                @if ($request->status == 'open')
                <x-button label="Approve/Reject" icon="o-check" @click="$wire.closeConfirm=true" class="btn-accent" />
                @endif
                @if ($open)
                <x-button label="Save" icon="o-paper-airplane" wire:click="save" spinner="save" class="btn-primary" />
                @endif
            </x-slot:actions>
        </x-header>
    </div>

    <div class="space-y-4">
        <x-card>
            <x-form wire:submit="save">
                <div class="space-y-4">
                    <div class="space-y-4 lg:space-y-0 lg:grid grid-cols-2 gap-4">
                        <x-input label="Code" wire:model="code" readonly />
                        <x-select label="Type" wire:model="type" :options="\App\Enums\RequestType::toSelect()" placeholder="-- Select --" :disabled="!$open" />
                        <x-select label="Resource Name" wire:model="requestable_type" :options="$resources" placeholder="-- Select --" :disabled="!$open" />
                        <x-input label="Resource ID" wire:model="requestable_id" :disabled="!$open" />
                        <x-textarea rows="4" label="Request Description" wire:model="description" :disabled="!$open" />
                    </div>
                </div>
            </x-form>
        </x-card>

        @isset($request->id)
        <div class="space-y-4 lg:space-y-0 lg:grid grid-cols-2 gap-4">
            <x-card>
                <x-other-info :data="$request" />
            </x-card>
            <x-logs :data="$request" />
        </div>
        @endisset

    </div>

    <x-modal wire:model="closeConfirm" title="Approve Confirmation" persistent>
        <div class="flex pb-2">
            Are you sure you want to process this request?
        </div>
        <x-slot:actions>
            <div class="flex items-center gap-4">
                <x-button label="Cancel" icon="o-x-mark" @click="$wire.closeConfirm = false" class="" />
                <x-button label="Reject" icon="o-x-mark" wire:click="reject" spinner="reject" class="btn-error" />
                <x-button label="Approve" icon="o-check" wire:click="approve" spinner="approve" class="btn-success" />

            </div>
        </x-slot:actions>
    </x-modal>
</div>
