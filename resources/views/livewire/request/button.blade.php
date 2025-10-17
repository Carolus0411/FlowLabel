<?php

use Illuminate\Support\Facades\Gate;
use Livewire\Volt\Component;
use Mary\Traits\Toast;
use App\Helpers\Cast;
use App\Helpers\Code;
use App\Models\Request;

new class extends Component {
    use Toast;

    public $model;
    public $code = '';
    public $type = '';
    public $description = '';
    public $requestable_id = '';
    public $requestable_type = '';
    public $requestable_code = '';

    public $requestModal = false;

    public function mount($model): void
    {
        $this->model = $model;
        $this->requestable_type = get_class($model);
        $this->requestable_id = $model->id;
        $this->requestable_code = $model->code ?? '';
    }

    public function with(): array
    {
        return [];
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

        Request::create($data);

        $this->requestModal = false;
        $this->success('Request successfully created.');
    }
}; ?>

<div>
    <x-button label="Request Void" @click="$wire.requestModal = true" icon="o-document-plus" class="btn-warning" />
    <x-modal wire:model="requestModal" title="Request Form" persistent>
        <div class="space-y-3">
            <x-select label="Type" wire:model="type" :options="\App\Enums\RequestType::toSelect()" placeholder="-- Select --" />
            <x-input label="Resource Type" wire:model="requestable_type" disabled />
            <div class="space-y-4 lg:space-y-0 lg:grid grid-cols-2 gap-4">
                <x-input label="Resource ID" wire:model="requestable_id" disabled />
                <x-input label="Resource Code" wire:model="requestable_code" disabled />
            </div>
            <x-textarea rows="4" label="Request Description" wire:model="description" />
        </div>
        <x-slot:actions>
            <div class="flex items-center gap-4">
                <x-button label="Cancel" icon="o-x-mark" @click="$wire.requestModal = false" class="" />
                <x-button label="Create Request" icon="o-check" wire:click="save" spinner="save" class="btn-success" />
            </div>
        </x-slot:actions>
    </x-modal>
</div>
