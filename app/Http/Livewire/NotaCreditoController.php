<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\NotaCredito;
use Livewire\WithPagination;
use App\Traits\GeneraJsonNota;
use App\Traits\FirmadorLocal;
use App\Traits\RecepcionDTENota;

class NotaCreditoController extends Component
{
    use WithPagination;
    use GeneraJsonNota;
    use FirmadorLocal;
    use RecepcionDTENota;

    public  $search, $selected_id, $pageTitle, $componentName;
    private $pagination = 10;

    public function mount()
    {
        $this->pageTitle = 'Listado';
        $this->componentName = 'Notas de Credito';
    }

    public function paginationView()
    {
        return 'vendor.livewire.bootstrap';
    }

    public function render()
    {
        $notas = NotaCredito::with('Rclientes:id,nombreCliente')
        ->where('estado', 'Cancelado')
        ->where(function ($query) {
            $query->where('codigo', 'like', '%' . $this->search . '%')
                  ->orWhere('numero', 'like', '%' . $this->search . '%')
                  ->orWhere('fecha', 'like', '%' . $this->search . '%')
                  ->orWhere('total', 'like', '%' . $this->search . '%')
                  ->orWhereHas('Rclientes', function ($q) {
                      $q->where('nombreCliente', 'like', '%' . $this->search . '%');
                  });
        })
        ->orderBy('fecha', 'desc')
        ->paginate($this->pagination);

        return view('livewire.nota_credito.nota-credito', [
            'notas' => $notas,
        ])
        ->extends('layouts.theme.app')
        ->section('content');
    }

    public function Generar($id)
    {
        //dd($json  = $this->generaJsonNota($id));
        //$firma = $this->FirmadorLocal($id, $json));
        $this->RecepcionDTENota($id);
    }
}
