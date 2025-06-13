@props([ 'status' ])
<div {{ $boxClass ?? '' }}>
    @if ($status == 'close')
    <x-badge value="Closed" {{ $attributes->merge(['class' => 'text-xs badge-success']) }} />
    @elseif ($status == 'open')
    <x-badge value="Open" {{ $attributes->merge(['class' => 'text-xs badge-primary']) }} />
    @elseif ($status == 'void')
    <x-badge value="Void" {{ $attributes->merge(['class' => 'text-xs badge-error']) }} />
    @endif
</div>
