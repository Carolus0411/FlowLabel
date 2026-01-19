<?php

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Spatie\SimpleExcel\SimpleExcelReader;
use Mary\Traits\Toast;
use App\Models\SalesInvoiceDirect;

new class extends Component {
    use Toast, WithFileUploads;

    public $file;

    public function mount(): void
    {
        Gate::authorize('import sales-invoice-direct');
    }

    public function save()
    {
        $valid = $this->validate([
            'file' => 'required|mimes:xlsx|max:2048',
        ]);

        $target = $this->file->path();

        DB::beginTransaction();

        try {

            if ( file_exists( $target ) ) {

                $rows = SimpleExcelReader::create($target)->getRows();
                $rows->each(function(array $row) {

                    if ( !empty($row['name']) )
                    {
                        if (!empty($row['id'])) {
                            $data['id'] = $row['id'];
                        }

                        if (!empty($row['id'])) {
                            SalesInvoiceDirect::where('id', $row['id'])->delete();
                        }

                        $data['code'] = $row['code'];

                        SalesInvoiceDirect::create($data);
                    }

                });
            }

            DB::commit();
            $this->success('Success','Sales invoice successfully imported.', redirectTo: route('sales-invoice-direct.index'));
        }
        catch (Exception $e)
        {
            DB::rollBack();
            logger()->error($e->getMessage());
            $this->error('Error','Sales invoice failed to import.', redirectTo: route('sales-invoice-direct.index'));
        }
    }
}; ?>

<div>
    <x-header title="Import Sales Invoice Direct" separator />
    <x-card>
        <x-form wire:submit="save">
            <x-file wire:model="file" label="File" hint="xlsx or csv" wire:target="save" wire:loading.attr="disabled" />
            <x-slot:actions>
                <x-button label="Cancel" link="{{ route('sales-invoice-direct.index') }}" wire:target="save" wire:loading.attr="disabled" />
                <x-button label="Import" icon="o-paper-airplane" spinner="save" type="submit" class="btn-primary" />
            </x-slot:actions>
        </x-form>
    </x-card>
</div>
