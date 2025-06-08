<?php

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Spatie\SimpleExcel\SimpleExcelReader;
use Mary\Traits\Toast;
use App\Models\Bank;

new class extends Component {
    use Toast, WithFileUploads;

    public $file;

    public function mount(): void
    {
        Gate::authorize('import bank');
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
                            Bank::where('id', $row['id'])->delete();
                        }

                        $data['name'] = $row['name'];
                        $data['is_active'] = $row['is_active'];

                        Bank::create($data);
                    }

                });
            }

            DB::commit();
            $this->success('Success','Bank successfully imported.', redirectTo: route('bank.index'));
        }
        catch (Exception $e)
        {
            DB::rollBack();
            logger()->error($e->getMessage());
            $this->error('Error','Bank failed to import.', redirectTo: route('bank.index'));
        }
    }
}; ?>

<div>
    <x-header title="Import Bank" separator />
    <x-card>
        <x-form wire:submit="save">
            <x-file wire:model="file" label="File" hint="xlsx or csv" wire:target="save" wire:loading.attr="disabled" />
            <x-slot:actions>
                <x-button label="Cancel" link="{{ route('bank.index') }}" wire:target="save" wire:loading.attr="disabled" />
                <x-button label="Import" icon="o-paper-airplane" spinner="save" type="submit" class="btn-primary" />
            </x-slot:actions>
        </x-form>
    </x-card>
</div>
