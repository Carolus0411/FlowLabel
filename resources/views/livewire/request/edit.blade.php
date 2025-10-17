<?php

use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
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
    public $status = '';
    public $description = '';
    public $requestable_id = '';
    public $requestable_type = '';
    public $requestable_code = '';
    public $response = '';

    public $open = true;
    public $closeConfirm = false;

    public function mount(): void
    {
        Gate::authorize('update request');
        $this->fill($this->request);
        $this->requestable_code = $this->request->requestable->code ?? '';
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
        $data = $this->validate([
            'type' => 'required',
            'description' => 'required',
            'requestable_id' => ['required', new \App\Rules\ResourceCheck($this->requestable_type)],
            'requestable_type' => 'required',
            'response' => Rule::when($close === 'reject', ['required']),
        ]);

        $this->request->update($data);

        if ($close == 'approve') {
            $this->approve();
        }

        if ($close == 'reject') {
            $this->reject();
        }

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
        \App\Jobs\RequestApprove::dispatchSync($this->request);
        $this->success('Request successfully updated.');
        $this->closeConfirm = false;
    }

    public function reject(): void
    {
        Gate::authorize('close request');
        $this->request->update([
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
                    <span>Update Request</span>
                    <x-request-badge :status="$request->status" class="uppercase !text-sm" />
                </div>
            </x-slot:title>
            <x-slot:actions>
                <x-button label="Back" link="{{ route('request.index') }}" icon="o-arrow-uturn-left" class="btn-soft" />
                @if ($request->status == 'open')
                <x-button label="Approve/Reject" icon="o-check" @click="$wire.closeConfirm=true" class="btn-info" />
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
                    <div class="space-y-4 lg:space-y-0 lg:grid grid-cols-3 gap-4">
                        <x-input label="Code" wire:model="code" readonly />
                        <x-input label="Status" wire:model="status" readonly />
                        <x-select label="Type" wire:model="type" :options="\App\Enums\RequestType::toSelect()" placeholder="-- Select --" :disabled="!$open" />

                        @empty($request->id)
                        <x-select label="Resource Name" wire:model="requestable_type" :options="$resources" placeholder="-- Select --" :disabled="!$open" />
                        <x-input label="Resource ID" wire:model="requestable_id" :readonly="!$open" />
                        @else
                        <x-select label="Resource Name" wire:model="requestable_type" :options="$resources" placeholder="-- Select --" disabled />
                        <x-input label="Resource ID" wire:model="requestable_id" disabled />
                        @endempty

                        <x-input label="Resource Code" wire:model="requestable_code" disabled />
                        <x-textarea rows="4" label="Request Description" wire:model="description" :readonly="!$open" @class(['!border-solid bg-base-200' => !$open]) />
                        @if (in_array($request->status, ['rejected', 'close']))
                        <x-textarea rows="4" label="Response" wire:model="response" :readonly="!$open" class="!border-solid bg-base-200" />
                        @endif
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

    <x-modal wire:model="closeConfirm" title="Approval Confirmation" persistent>
        <div class="space-y-3">
            {{-- <p>Are you sure you want to process this request?</p> --}}
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
