<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class AppBrand extends Component
{
    /**
     * Create a new component instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return <<<'HTML'
                <a href="{{ route('order-label.dashboard') }}" wire:navigate>
                    <!-- Hidden when collapsed -->
                    <div {{ $attributes->class(["hidden-when-collapsed hidden lg:block"]) }}>
                        <div class="flex items-center gap-2 w-fit">
                            <x-icon name="o-cube" class="w-6 -mb-1.5 text-purple-500" />
                            <span class="font-bold text-3xl me-3 bg-gradient-to-r from-purple-500 to-pink-300 bg-clip-text text-transparent ">
                                FlowLabels
                            </span>
                        </div>
                    </div>

                    <!-- Display on Mobile -->
                    <div class="block lg:hidden">
                        <x-icon name="o-cube" class="w-8 -mb-1.5 text-purple-500" />
                    </div>

                    <!-- Display when collapsed -->
                    <div class="display-when-collapsed hidden mx-5 mt-5 mb-1 h-[28px]">
                        <x-icon name="s-cube" class="w-6 -mb-1.5 text-purple-500" />
                    </div>
                </a>
            HTML;
    }
}
