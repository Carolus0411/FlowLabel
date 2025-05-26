<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;

class Home extends Component
{
    #[Layout('site.layouts.app')]
    public function render()
    {
        return view('site.home')->title('Home');
    }
}
