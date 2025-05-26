<?php

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Collection;
use Livewire\Volt\Component;
use Mary\Traits\Toast;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

new class extends Component {
    use Toast;

    public ?string $meta_description = '';
    public ?string $meta_keyword = '';
    public ?Collection $meta_keywords;

    public function mount(): void
    {
        Gate::authorize('view general setting');

        $this->meta_description = settings('meta_description');
        $this->meta_keywords = Str::of(settings('meta_keyword'))->explode(',');
    }

    public function save(): void
    {
        $data = $this->validate([
            'meta_description' => 'nullable',
            'meta_keywords' => 'nullable',
        ]);

        $data['meta_keyword'] = $this->meta_keywords->join(',');
        unset($data['meta_keywords']);

        settings($data);

        $this->success('General setting successfully updated.');
    }
}; ?>

<div>
    <x-header title="General Setting" separator>
        {{-- <x-slot:actions>
            <x-button label="Back" link="{{ route('brand.index') }}" icon="o-arrow-uturn-left" />
        </x-slot:actions> --}}
    </x-header>

    <x-form wire:submit="save">
        <x-card title="Global Meta">
            <div class="space-y-4">
                <x-textarea rows="3" label="Meta Description" wire:model="meta_description" hint="Short summary of a web page's content, typically displayed below the page title in search engine results." />
                <x-tags label="Meta Keyword" wire:model="meta_keywords" hint="Meta Keywords are a specific type of meta tag that appear in the HTML code of a Web page and help tell search engines what the topic of the page is." />
            </div>
        </x-card>
        <x-slot:actions>
            <x-button label="Save" icon="o-paper-airplane" spinner="save" type="submit" class="btn-primary" />
        </x-slot:actions>
    </x-form>
</div>
