<?php

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Spatie\SimpleExcel\SimpleExcelReader;
use Mary\Traits\Toast;
use App\Models\Supplier;
use Illuminate\Support\Facades\Schema;

new class extends Component {
    use Toast, WithFileUploads;

    public $file;

    public function mount(): void
    {
        Gate::authorize('import supplier');
    }

    public function save()
    {
        if (! Schema::hasTable('supplier')) {
            $this->error('Database table `supplier` does not exist. Please run migrations.');
            return;
        }

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
                            Supplier::where('id', $row['id'])->delete();
                        }

                        $data['code'] = $row['code'] ?? null;
                        $data['name'] = $row['name'] ?? null;
                        $data['contact_name'] = $row['contact_name'] ?? null;
                        $data['address_1'] = $row['address_1'] ?? null;
                        $data['address_2'] = $row['address_2'] ?? null;
                        $data['telephone'] = $row['telephone'] ?? null;
                        $data['mobile_phone'] = $row['mobile_phone'] ?? null;
                        $data['email'] = $row['email'] ?? null;
                        $data['npwp'] = $row['npwp'] ?? null;
                        $data['information'] = $row['information'] ?? null;
                        $data['term_of_payment'] = is_numeric($row['term_of_payment'] ?? null) ? intval($row['term_of_payment']) : 0;
                        $data['is_active'] = $row['is_active'] ?? 1;

                        Supplier::create($data);
                    }

                });
            }

            DB::commit();
            $this->success('Success','Supplier successfully imported.', redirectTo: route('supplier.index'));
        }
        catch (Exception $e)
        {
            DB::rollBack();
            logger()->error($e->getMessage());
            $this->error('Error','Supplier failed to import.', redirectTo: route('supplier.index'));
        }
    }
}; ?>

<div>
    <x-header title="Import Supplier" separator />
    <x-card>
        <x-form wire:submit="save">
            <div class="mb-2 text-sm text-gray-600">
                Template can be created by exporting data from the Supplier page using the Export button.
            </div>
            <x-file wire:model="file" label="File" hint="xlsx or csv" wire:target="save" wire:loading.attr="disabled" />
            <x-slot:actions>
                <x-button label="Cancel" link="{{ route('supplier.index') }}" wire:target="save" wire:loading.attr="disabled" />
                <x-button label="Import" icon="o-paper-airplane" spinner="save" type="submit" class="btn-primary" />
            </x-slot:actions>
        </x-form>
    </x-card>
</div>
