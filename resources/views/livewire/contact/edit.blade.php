<?php

use Illuminate\Support\Facades\Gate;
use Livewire\Volt\Component;
use Mary\Traits\Toast;
use App\Models\Contact;

new class extends Component {
    use Toast;

    public Contact $contact;

    public string $name = '';
    public bool $is_active = false;

    public function mount(): void
    {
        Gate::authorize('update contact');
        $this->fill($this->contact);
    }

    public function save(): void
    {
        $data = $this->validate([
            'name' => 'required',
            'is_active' => 'boolean',
        ]);

        $this->contact->update($data);

        $this->success('Contact successfully updated.', redirectTo: route('contact.index'));
    }
}; ?>

<div>
    <x-header title="Update Contact" separator>
        <x-slot:actions>
            <x-button label="Back" link="{{ route('contact.index') }}" icon="o-arrow-uturn-left" />
        </x-slot:actions>
    </x-header>

    <x-form wire:submit="save">
        <x-card>
            <div class="space-y-4">
                <x-input label="Name" wire:model="name" />
                <x-toggle label="Active" wire:model="is_active" />
            </div>
        </x-card>
        <x-slot:actions>
            <x-button label="Cancel" link="{{ route('contact.index') }}" />
            <x-button label="Save" icon="o-paper-airplane" spinner="save" type="submit" class="btn-primary" />
        </x-slot:actions>
    </x-form>
</div>
