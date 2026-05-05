<?php

namespace App\Http\Livewire;

use Livewire\Component;

class AdelantoCredito extends Component
{
    public function render()
    {
        return view('livewire.adelanto_credito.adelanto_credito')
        ->extends('layouts.theme.app')
        ->section('content');
    }
}
