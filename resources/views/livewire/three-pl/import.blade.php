<?php

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Spatie\SimpleExcel\SimpleExcelReader;
use Mary\Traits\Toast;
use App\Models\ThreePl;
use Illuminate\Support\Facades\Schema;

new class extends Component {
    use Toast, WithFileUploads;

    public $file;

    public function mount(): void
    {
        Gate::authorize('import three-pl');
    }

    public function save()
    {
        if (! Schema::hasTable('three_pls')) {
            $this->error('Database table `three_pls` does not exist. Please run migrations.');
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
                            ThreePl::where('id', $row['id'])->delete();
                        }

                        $data['code'] = $row['code'] ?? null;
                        $data['name'] = $row['name'] ?? null;
                        $data['is_active'] = $row['is_active'] ?? 1;

                        ThreePl::create($data);
                    }

                });
            }

            DB::commit();
            $this->success('Success','3PL successfully imported.', redirectTo: route('three-pl.index'));
        }
        catch (Exception $e)
        {
            DB::rollBack();
            logger()->error($e->getMessage());
            $this->error('Error','3PL failed to import.', redirectTo: route('three-pl.index'));
        }
    }
}; ?>

<div>
    <x-header title="Import 3PL" separator />
    <x-card>
        <x-form wire:submit="save">
            <div class="mb-2 text-sm text-gray-600">
                Template can be created by exporting data from the 3PL page using the Export button.
            </div>
            <x-file wire:model="file" label="File" hint="xlsx or csv" wire:target="save" wire:loading.attr="disabled" />
            <x-slot:actions>
                <x-button label="Cancel" link="{{ route('three-pl.index') }}" />
                <x-button label="Import" icon="o-paper-airplane" spinner="save" type="submit" class="btn-primary" />
            </x-slot:actions>
        </x-form>
    </x-card>
</div>
