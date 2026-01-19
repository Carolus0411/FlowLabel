<?php

namespace App\Http\Controllers;

use App\Models\Intercash;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;

class IntercashController extends Controller
{
    public function index()
    {
        Gate::authorize('view intercash');
        return view('livewire.intercash.index');
    }

    public function create()
    {
        Gate::authorize('create intercash');
        $intercash = new Intercash();
        $intercash->code = '';
        $intercash->status = 'open';
        return view('livewire.intercash.edit', ['intercash' => $intercash]);
    }

    public function edit(Intercash $intercash)
    {
        Gate::authorize('update intercash');
        return view('livewire.intercash.edit', compact('intercash'));
    }

    public function show(Intercash $intercash)
    {
        Gate::authorize('view intercash');
        return view('livewire.intercash.show', compact('intercash'));
    }
}
