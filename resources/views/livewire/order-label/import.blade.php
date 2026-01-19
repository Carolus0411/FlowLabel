<?php

use Illuminate\Support\Facades\Gate;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Mary\Traits\Toast;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Bus;
use App\Jobs\ProcessOrderLabelImport;
use App\Models\ThreePl;

new class extends Component {
    use Toast, WithFileUploads;

    public $file;
    public bool $processing = false;
    public $batchId;
    public $three_pl_id = null;

    public function mount(): void {
        Gate::authorize('view order-label');
    }

    public function with(): array
    {
        return [
            'threePls' => ThreePl::isActive()->orderBy('name')->get(),
        ];
    }

    public function save(): void {
        if (! Schema::hasTable('order_label')) {
            $this->error('Database table `order_label` does not exist. Please run migrations.');
            return;
        }

        $this->validate([
            'three_pl_id' => 'required|exists:three_pls,id',
            'file' => 'required|file|mimes:pdf|max:10240', // 10MB max
        ]);

        $this->processing = true;

        try {
            $uploadedFile = $this->file;
            $originalName = $uploadedFile->getClientOriginalName();

            // Store the uploaded file permanently in a 'temp-import' folder so Queue can access it
            // We cannot use /tmp because queue worker might be separate (though local here it's fine)
            // Storing in 'app/import-temp'
            $path = $uploadedFile->storeAs('import-temp', uniqid() . '_' . $originalName);
            
            // Create a batch so we can track it
            // We'll dispatch a single job inside a batch for better tracking
            $batch = Bus::batch([
                new ProcessOrderLabelImport($path, $originalName, auth()->id(), $this->three_pl_id)
            ])->name('Import PDF: ' . $originalName)
              ->dispatch();

            $this->batchId = $batch->id;
            $this->processing = false;
            
            $this->success("Import queued! You can track progress in the Queue Log.");
            
        } catch (\Exception $e) {
            $this->processing = false;
            $this->error('Failed to queue PDF: ' . $e->getMessage());
        }
    }

    public function resetForm()
    {
        $this->file = null;
        $this->processing = false;
        $this->batchId = null;
        $this->three_pl_id = null;
    }
};
?>

<div>
    <x-header title="Import PDF - Order Label" separator />

    @if($batchId)
        <x-card title="Import Queued" class="bg-blue-50 border-blue-200">
             <div class="p-4">
                <p class="text-blue-800 font-semibold text-lg mb-2">Import has been started in the background.</p>
                <p class="mb-4">You can leave this page or upload another file. The system will process the PDF in the background.</p>
                
                <div class="flex gap-3">
                    <x-button label="Check Progress in Queue Log" link="{{ route('settings.queue-log') }}" class="btn-primary" />
                    <x-button label="Upload Another" wire:click="resetForm" class="btn-outline" />
                </div>
             </div>
        </x-card>
    @else
        <x-card>
            <x-form wire:submit="save">

                <div class="mb-4 p-4 bg-blue-50 rounded-lg border border-blue-200">
                    <h3 class="font-semibold text-blue-800 mb-2">PDF Import Instructions:</h3>
                    <ul class=Select Platform (3PL) first</li>
                        <li>• Upload a PDF file (max 10MB)</li>
                        <li>• The file will be processed in the background (Queue)</li>
                        <li>• Large files (500+ pages) may take several minutes</li>
                    </ul>
                </div>

                <x-choices
                    label="Platform"
                    wire:model.live="three_pl_id"
                    :options="$threePls"
                    option-label="name"
                    option-value="id"
                    placeholder="-- Pilih Platform --"
                    single
                    searchable />

                @if($three_pl_id)
                    <x-file wire:model="file" label="PDF File"
                           hint="Select PDF file to split (max 10MB)"
                           accept=".pdf"
                           wire:target="save"
                           wire:loading.attr="disabled" />
                @endif

                <x-slot:actions>
                    <x-button label="Cancel" link="{{ route('order-label.index') }}"
                             wire:target="save" wire:loading.attr="disabled" />
                    <x-button label="Start Import Process" icon="o-rocket-launch"
                             spinner="save" type="submit" class="btn-primary"
                             :disabled="$processing" />
                </x-slot:actions>
            </x-form>
        </x-card>
    @endif
</div>
