<?php

use Illuminate\Support\Facades\Gate;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Mary\Traits\Toast;
use App\Models\Company;

new class extends Component {
    use Toast, WithFileUploads;

    public ?Company $company = null;
    public string $code = '';
    public string $name = '';
    public string $address = '';
    public string $phone = '';
    public string $email = '';
    public string $website = '';
    public string $type = 'main';
    public string $description = '';
    public int $is_active = 1;
    public $logo;
    public ?string $logoPath = null;

    public function mount(Company $company = null): void
    {
        $this->company = $company;

        if ($company) {
            Gate::authorize('update company');
            $this->code = $company->code;
            $this->name = $company->name;
            $this->address = $company->address ?? '';
            $this->phone = $company->phone ?? '';
            $this->email = $company->email ?? '';
            $this->website = $company->website ?? '';
            $this->type = $company->type ?? 'main';
            $this->description = $company->description ?? '';
            $this->is_active = $company->is_active ?? 1;
            $this->logoPath = $company->logo;
        } else {
            Gate::authorize('create company');
        }
    }

    public function save(): void
    {
        $validated = $this->validate([
            'code' => 'required|string|max:50' . ($this->company ? '|unique:companies,code,' . $this->company->id : '|unique:companies'),
            'name' => 'required|string|max:150',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:100',
            'website' => 'nullable|string|max:100',
            'type' => 'required|in:main,branch',
            'description' => 'nullable|string',
            'is_active' => 'required|boolean',
            'logo' => 'nullable|mimes:jpg,jpeg,png,gif,svg|max:2048',
        ]);

        // Handle logo upload
        if ($this->logo) {
            $logoPath = $this->logo->store('company-logos', 'public');
            // Delete old logo if exists
            if ($this->company && $this->company->logo) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($this->company->logo);
            }
            $validated['logo'] = $logoPath;
        } elseif ($this->company) {
            $validated['logo'] = $this->company->logo;
        }

        if ($this->company) {
            $this->company->update($validated);
            $this->success('Company updated successfully.');
        } else {
            Company::create($validated);
            $this->success('Company created successfully.');
        }

        $this->redirectRoute('company.index');
    }

    public function removeLogo(): void
    {
        if ($this->company && $this->company->logo) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($this->company->logo);
            $this->company->update(['logo' => null]);
            $this->logoPath = null;
            $this->success('Logo removed successfully.');
        }
    }

    public function cancel(): void
    {
        $this->redirectRoute('company.index');
    }
}; ?>

<div>
    <x-header :title="$company ? 'Edit Company' : 'Create Company'" separator back-icon="o-arrow-left" back-link="{{ route('company.index') }}">
    </x-header>

    <x-card>
        <x-form wire:submit="save">
            <x-input label="Code" wire:model="code" required />
            <x-input label="Company Name" wire:model="name" required />

            <x-select label="Type" wire:model="type" :options="[
                ['id' => 'main', 'name' => 'Main Office'],
                ['id' => 'branch', 'name' => 'Branch']
            ]" required />

            <x-input label="Address" wire:model="address" type="textarea" />
            <x-input label="Phone" wire:model="phone" />
            <x-input label="Email" wire:model="email" type="email" />
            <x-input label="Website" wire:model="website" />
            <x-input label="Description" wire:model="description" type="textarea" />

            <x-checkbox label="Active" wire:model="is_active" />

            <!-- Logo Upload -->
            <div class="divider">Logo Upload</div>

            @if($logoPath)
            <div class="mb-4">
                <p class="text-sm font-semibold mb-2">Current Logo:</p>
                <img src="{{ asset('storage/' . $logoPath) }}" alt="Company Logo" class="h-24 w-24 object-cover rounded border">
                <x-button label="Remove Logo" @click="$wire.removeLogo()" icon="o-trash" class="btn-error btn-sm mt-2" />
            </div>
            @endif

            <x-file label="Upload Logo" wire:model="logo" accept="image/*,.svg" hint="JPG, PNG, GIF, SVG (Max 2MB)" />

            <x-slot:actions>
                <x-button label="Cancel" @click="$wire.cancel()" />
                <x-button label="{{ $company ? 'Update' : 'Create' }}" type="submit" class="btn-primary" />
            </x-slot:actions>
        </x-form>
    </x-card>
</div>
