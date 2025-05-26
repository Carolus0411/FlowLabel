<?php

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Spatie\SimpleExcel\SimpleExcelReader;
use Mary\Traits\Toast;
use App\Models\Coa;
use App\Models\ServiceCharge;

new class extends Component {
    use Toast, WithFileUploads;

    public $file;

    public function mount(): void
    {
        Gate::authorize('import service charge');
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

                    if ( !empty($row['code']) AND !empty($row['name']) )
                    {
                        if (!empty($row['id'])) {
                            $data['id'] = $row['id'];
                        }

                        if (!empty($row['id'])) {
                            ServiceCharge::where('id', $row['id'])->delete();
                        }

                        if (!empty($row['coa_buying'])) {
                            $buying_coa_id = Coa::where('code', $row['coa_buying'])->first()->id ?? 0;
                        }

                        if (!empty($row['coa_selling'])) {
                            $selling_coa_id = Coa::where('code', $row['coa_selling'])->first()->id ?? 0;
                        }

                        $data['code'] = $row['code'];
                        $data['name'] = $row['name'];
                        $data['type'] = $row['type'];
                        $data['buying_coa_id'] = $buying_coa_id;
                        $data['selling_coa_id'] = $selling_coa_id;
                        $data['is_active'] = $row['is_active'] ?? '1';

                        ServiceCharge::create($data);
                    }

                });
            }

            DB::commit();
            $this->success('Success','Service charge successfully imported.', redirectTo: route('service-charge.index'));
        }
        catch (Exception $e)
        {
            DB::rollBack();
            logger()->error($e->getMessage());
            $this->error('Error','Service charge failed to import.', redirectTo: route('service-charge.index'));
        }
    }
}; ?>

<div>
    <x-header title="Import Service Charge" separator />
    <x-card>
        <x-form wire:submit="save">
            <x-file wire:model="file" label="File" hint="xlsx or csv" wire:target="save" wire:loading.attr="disabled" />
            <x-slot:actions>
                <x-button label="Cancel" link="{{ route('service-charge.index') }}" wire:target="save" wire:loading.attr="disabled" />
                <x-button label="Import" icon="o-paper-airplane" spinner="save" type="submit" class="btn-primary" />
            </x-slot:actions>
        </x-form>
    </x-card>
</div>
