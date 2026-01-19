<?php

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Carbon;
use Livewire\Volt\Component;
use Mary\Traits\Toast;
use App\Models\Intercash;

new class extends Component {
    use Toast;

    public function mount(): void
    {
        Gate::authorize('create intercash');

        $intercash = Intercash::create([
            'code' => uniqid('IC/'),
            'date' => Carbon::now(),
            'status' => 'open',
        ]);

        $this->redirectRoute('intercash.edit', $intercash->id);
    }
}; ?>

<div>
    <x-header title="Create Intercash" separator>
        <x-slot:actions>
            <x-button label="Back" link="{{ route('intercash.index') }}" icon="o-arrow-uturn-left" />
        </x-slot:actions>
    </x-header>
    <x-card>
        <div class="flex justify-center items-center p-8">
            <x-loading class="text-primary loading-lg" />
            <span class="ml-2">Creating Intercash...</span>
        </div>
    </x-card>
</div>
