<?php

namespace App\Http\Livewire;

use App\Traits\GenerarJsonCorteX;
use Livewire\Component;

class Pruebas extends Component
{
    use GenerarJsonCorteX;

    public function mount()
    {
        //');
         $this->GenerarJsonCorteX(19);
    }
    public function render()
    {
        $this->emit('print-ticket', $this->GenerarJsonCorteX(19));

        return view('livewire.pruebas')
        ->extends('layouts.theme.app')
        ->section('content');;
    }

    protected $listeners = [
        'print-ticket' => 'printTicket',
    ];
}
