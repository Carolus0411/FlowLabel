@props([
    'title' => 'Filters',
    'name' => 'drawer',
    'action' => 'search',
    'clear' => 'clear'
])
<x-drawer wire:model="{{ $name }}" title="{{ $title }}" right separator with-close-button {{ $attributes->merge(['class' => 'lg:w-1/3']) }}>
    <x-form wire:submit="{{ $action }}">
        <div class="grid gap-4">
            {{ $slot }}
        </div>
        <x-slot:actions>
            <x-button label="Reset" icon="o-x-mark" wire:click="{{ $clear }}" spinner="{{ $clear }}" />
            <x-button label="Search" icon="o-magnifying-glass" spinner="{{ $action }}" type="submit" class="btn-primary" />
        </x-slot:actions>
    </x-form>
</x-drawer>
