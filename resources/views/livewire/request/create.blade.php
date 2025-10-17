<?php

use Illuminate\Support\Facades\Gate;
use Livewire\Volt\Component;
use Mary\Traits\Toast;
use App\Helpers\Cast;
use App\Helpers\Code;
use App\Models\Request;

new class extends Component {
    use Toast;

    public $code = '';
    public $type = '';
    public $description = '';
    public $requestable_id = '';
    public $requestable_type = '';
    public $response = '';

    public $open = true;
    public $closeConfirm = false;

    public function mount(): void
    {
        Gate::authorize('update requests');
    }

    public function with(): array
    {
        $this->open = true;

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
        $data = $this->validate([
            'type' => 'required',
            'description' => 'required',
            'requestable_id' => ['required', new \App\Rules\ResourceCheck($this->requestable_type)],
            'requestable_type' => 'required',
        ]);

        $code = Code::auto('REQ');
        $data['code'] = $code;

        $request = Request::create($data);

        if ($close == 'approve') {
            $this->approve($request);
        }

        if ($close == 'reject') {
            $this->reject($request);
        }

        $this->success('Request successfully updated.', redirectTo: route('request.index'));
    }

    public function approve($request): void
    {
        Gate::authorize('close request');
        \App\Jobs\RequestApprove::dispatchSync($request);
        $this->success('Request successfully updated.');
        $this->closeConfirm = false;
    }

    public function reject($request): void
    {
        Gate::authorize('close request');
        $request->update([
            'status' => 'rejected',
            'response' => $this->response,
        ]);

        $this->success('Request successfully updated.');
        $this->closeConfirm = false;
    }
}; ?>

<div>
    <div class="lg:top-[65px] lg:sticky z-10 bg-base-200 pb-0 pt-3">
        <x-header separator>
            <x-slot:title>
                <div class="flex items-center-safe gap-4">
                    <span>Create Request</span>
                </div>
            </x-slot:title>
            <x-slot:actions>
                <x-button label="Back" link="{{ route('request.index') }}" icon="o-arrow-uturn-left" class="btn-soft" />
                <x-button label="Approve" icon="o-check" @click="$wire.closeConfirm=true" class="btn-success" />
                <x-button label="Save" icon="o-paper-airplane" wire:click="save" spinner="save" class="btn-primary" />
            </x-slot:actions>
        </x-header>
    </div>

    <div class="space-y-4">
        <x-card>
            <x-form wire:submit="save">
                <div class="space-y-4">
                    <div class="space-y-4 lg:space-y-0 lg:grid grid-cols-2 gap-4">
                        <x-input label="Code" wire:model="code" readonly :disabled="!$open" />
                        <x-select label="Type" wire:model="type" :options="\App\Enums\RequestType::toSelect()" placeholder="-- Select --" />
                        <x-select label="Resource Name" wire:model="requestable_type" :options="$resources" placeholder="-- Select --" />
                        <x-input label="Resource ID" wire:model="requestable_id" />
                        <x-textarea rows="4" label="Request Description" wire:model="description" />
                    </div>
                </div>
            </x-form>
        </x-card>
    </div>

    <x-modal wire:model="closeConfirm" title="Approval Confirmation" persistent>
        <div class="space-y-3">
            <p>Are you sure you want to process this request?</p>
            <x-textarea rows="4" label="Response notes" wire:model="response" />
        </div>
        <x-slot:actions>
            <div class="flex items-center gap-4">
                <x-button label="Cancel" icon="o-arrow-uturn-left" @click="$wire.closeConfirm = false" class="" />
                <x-button label="Reject" icon="o-x-mark" wire:click="save('reject')" spinner="save('reject')" class="btn-error" />
                <x-button label="Approve" icon="o-check" wire:click="save('approve')" spinner="save('approve')" class="btn-success" />

            </div>
        </x-slot:actions>
    </x-modal>
</div>
