@props([ 'status' ])
<div class="flex items-center {{ $boxClass ?? '' }}">
    @if ($status == 'approved')
    <x-badge value="Approved" {{ $attributes->merge(['class' => 'text-xs badge-success']) }} />
    @elseif ($status == 'open')
    <x-badge value="Open" {{ $attributes->merge(['class' => 'text-xs badge-primary']) }} />
    @elseif ($status == 'rejected')
    <x-badge value="Rejected" {{ $attributes->merge(['class' => 'text-xs badge-error']) }} />
    @endif
</div>
