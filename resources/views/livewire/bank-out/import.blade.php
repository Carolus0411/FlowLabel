<?php

use Spatie\LivewireFilepond\WithFilePond;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Spatie\SimpleExcel\SimpleExcelReader;
use Mary\Traits\Toast;
use App\Models\BankOut;

new class extends Component {
    use Toast, WithFileUploads, WithFilePond;

    public $file;

    public function mount(): void
    {
        Gate::authorize('import bank-out');
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

                    if ( !empty($row['code']) )
                    {
                        if (!empty($row['id'])) {
                            $data['id'] = $row['id'];
                        }

                        if (!empty($row['id'])) {
                            BankOut::where('id', $row['id'])->delete();
                        }

                        $data['code'] = $row['code'];
                        $data['date'] = $row['date'];
                        $data['note'] = $row['note'];
                        $data['bank_account_id'] = $row['bank_account_id'];
                        $data['contact_id'] = $row['contact_id'];
                        $data['total_amount'] = 0;
                        $data['status'] = $row['status'];
                        $data['saved'] = $row['saved'];

                        BankOut::create($data);
                    }

                });
            }

            DB::commit();
            $this->success('Success','Bank successfully imported.', redirectTo: route('bank-out.index'));
        }
        catch (Exception $e)
        {
            DB::rollBack();
            logger()->error($e->getMessage());
            $this->error('Error','Bank failed to import.', redirectTo: route('bank-out.index'));
        }
    }
}; ?>

<div>
    <x-header title="Import Bank Out" separator />
    <div
        x-on:filepond-upload-started="$refs.submit.disabled = true"
        x-on:filepond-upload-completed="$refs.submit.disabled = false"
    >
        <x-card>
            <x-form wire:submit="save">
                {{-- <x-file wire:model="file" label="File" hint="xlsx or csv" wire:target="save" wire:loading.attr="disabled" /> --}}
                <div>
                    <x-filepond::upload wire:model="file" required />
                    @error('file') <div class="text-error text-xs">{{ $message }}</div> @enderror
                </div>

                <x-slot:actions>
                    <x-button label="Cancel" link="{{ route('bank-out.index') }}" wire:target="save" wire:loading.attr="disabled" />
                    <x-button label="Import" icon="o-paper-airplane" spinner="save" type="submit" class="btn-primary" x-ref="submit" />
                </x-slot:actions>
            </x-form>
        </x-card>
    </div>

</div>
