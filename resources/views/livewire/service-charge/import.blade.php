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
        Gate::authorize('import service-charge');
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

                        // map coa codes to ids
                        $buying_coa_id = 0;
                        $selling_coa_id = 0;
                        $service_charge_group_id = null;

                        if (!empty($row['coa_buying'])) {
                            $buying_coa_id = Coa::where('code', $row['coa_buying'])->first()->id ?? 0;
                        }

                        if (!empty($row['coa_selling'])) {
                            $selling_coa_id = Coa::where('code', $row['coa_selling'])->first()->id ?? 0;
                        }

                        if (!empty($row['group'])) {
                            $group = \App\Models\ServiceChargeGroup::where('code', $row['group'])->first();
                            $service_charge_group_id = $group->id ?? null;
                        }


                        $data['code'] = $row['code'];
                        $data['name'] = $row['name'];
                        $data['transport'] = $row['transport'];
                        $data['type'] = $row['type'];
                        $data['buying_coa_id'] = $buying_coa_id;
                        $data['selling_coa_id'] = $selling_coa_id;
                        $data['service_charge_group_id'] = $service_charge_group_id;
                        $data['is_active'] = $row['is_active'] ?? '1';

                        // update existing record by code if found, otherwise create a new one
                        ServiceCharge::updateOrCreate([
                            'code' => $data['code'],
                        ], $data);
                    }

                });
            }

            DB::commit();
            $this->success('Success','Items Master successfully imported.', redirectTo: route('items-master.index'));
        }
        catch (Exception $e)
        {
            DB::rollBack();
            logger()->error($e->getMessage());
            $this->error('Error','Items Master failed to import.', redirectTo: route('items-master.index'));
        }
    }
}; ?>

<div>
    <x-header title="Import Items Master" separator />
    <x-card>
        <x-form wire:submit="save">
            <x-file wire:model="file" label="File" hint="xlsx or csv" wire:target="save" wire:loading.attr="disabled" />
            <x-slot:actions>
                <x-button label="Cancel" link="{{ route('items-master.index') }}" wire:target="save" wire:loading.attr="disabled" />
                <x-button label="Import" icon="o-paper-airplane" spinner="save" type="submit" class="btn-primary" />
            </x-slot:actions>
        </x-form>
    </x-card>
</div>
