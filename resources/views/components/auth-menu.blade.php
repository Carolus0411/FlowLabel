@props([])

<ul {!! $attributes->merge(['class' => 'menu w-[250px] bg-base-200 lg:bg-none text-base-content min-h-screen lg:min-h-auto p-6 lg:px-4 lg:py-0 gap-2']) !!}>
    <li><a href="/profile" wire:navigate wire:ignore.self wire:current="menu-active">Profile</a></li>
    <li><a href="/change-password" wire:navigate wire:ignore.self wire:current="menu-active">Change Password</a></li>
    <li><form method="POST" action="{{ route('logout') }}" class="flex w-full">
        @csrf
        <button type="submit" class="flex grow w-full p-0 m-0 cursor-pointer">
            {{ __('Log Out') }}
        </button>
    </form>
    </li>
</ul>
