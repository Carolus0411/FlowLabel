@props([])

<div {!! $attributes->merge(['class' => 'drawer lg:drawer-open z-40']) !!}>
    <input id="my-drawer-2" type="checkbox" class="drawer-toggle" />
    <div class="drawer-content lg:!h-auto">
        {{-- Content --}}

        <label for="my-drawer-2" class="btn btn-primary drawer-button lg:hidden w-full mb-10">
            Show Menu
        </label>

        {{ $slot }}

    </div>
    <div class="drawer-side lg:!h-auto">
        <label for="my-drawer-2" aria-label="close sidebar" class="drawer-overlay"></label>

        {{-- Sidebar --}}
        {{ $sidebar }}

    </div>
</div>
