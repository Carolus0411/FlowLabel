@props([ 'status' ])
<div class="flex items-center {{ $boxClass ?? '' }}">
    @if ($status)
    <x-badge value="Active" {{ $attributes->merge(['class' => 'text-xs uppercase badge-success badge-soft']) }} />
    @else
    <x-badge value="Inactive" {{ $attributes->merge(['class' => 'text-xs uppercase badge-error badge-soft']) }} />
    @endif
</div>
