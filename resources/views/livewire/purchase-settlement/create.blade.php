<?php

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Carbon;
use Livewire\Volt\Component;
use Mary\Traits\Toast;
use App\Models\PurchaseSettlement;

new class extends Component {
    use Toast;

    public function mount(): void
    {
        Gate::authorize('create purchase-settlement');

        // Create a draft settlement and redirect to edit
        $purchaseSettlement = PurchaseSettlement::create([
            'code' => uniqid(),
            'date' => Carbon::now(),
            'status' => 'open',
            'saved' => 0,
        ]);

        $this->redirectRoute('purchase-settlement.edit', $purchaseSettlement->id);
    }
}; ?>

<div>
    <x-header title="Create Purchase Settlement" separator>
        <x-slot:actions>
            <x-button label="Back" link="{{ route('purchase-settlement.index') }}" icon="o-arrow-uturn-left" />
        </x-slot:actions>
    </x-header>
    <x-card>
        <div class="flex justify-center items-center p-8">
            <x-loading class="text-primary loading-lg" />
            <span class="ml-2">Creating settlement...</span>
        </div>
    </x-card>
</div>
