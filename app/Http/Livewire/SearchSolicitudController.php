<?php

namespace App\Http\Livewire;

use Livewire\Component;

class SearchSolicitudController extends Component
{
    public $searchSolicutd;

    public function render()
    {
        return view('livewire.existencias.search-solicitud');
    }
}
