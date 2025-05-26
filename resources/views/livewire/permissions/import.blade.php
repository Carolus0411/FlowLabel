<?php

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Spatie\SimpleExcel\SimpleExcelReader;
use Mary\Traits\Toast;
use Spatie\Permission\Models\Permission;

new class extends Component {
    use Toast, WithFileUploads;

    public $file;

    public function mount(): void
    {
        Gate::authorize('import permissions');
    }

    public function save()
    {
        $valid = $this->validate([
            'file' => 'required|mimes:xlsx|max:2048',
        ]);

        $status = $this->file->store('/', 'local');
        $target = storage_path('app/'.$status);

        DB::beginTransaction();

        try {

            if ( file_exists( $target ) ) {

                $rows = SimpleExcelReader::create($target)->getRows();
                $rows->each(function(array $row) {

                    if ( ! empty($row['resource']) AND !empty($row['name']) )
                    {
                        if (!empty($row['id'])) {
                            $data['id'] = strtolower($row['id']);
                        }

                        $data['resource'] = strtolower($row['resource']);
                        $data['name'] = strtolower($row['name']);
                        $data['guard_name'] = 'web';

                        Permission::create($data);
                    }

                });
            }

            DB::commit();
            $this->success('Permission has been imported.', redirectTo: route('permissions.index'));
        }
        catch (Exception $e)
        {
            DB::rollBack();
            logger()->error($e->getMessage());
            $this->error('Permission failed to import.', redirectTo: route('permissions.index'));
        }
    }
}; ?>

<div>
    <x-header title="Import Permissions" separator />
    <x-card>
        <x-form wire:submit="save">
            <x-file wire:model="file" label="File" hint="xlsx or csv" />
            <x-slot:actions>
                <x-button label="Cancel" link="{{ route('permissions.index') }}" />
                <x-button label="Import" icon="o-paper-airplane" spinner="save" type="submit" class="btn-primary" />
            </x-slot:actions>
        </x-form>
    </x-card>
</div>
