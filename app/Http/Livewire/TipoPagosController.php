<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\TipoPagos;
use Livewire\WithPagination;

class TipoPagosController extends Component
{
    use WithPagination;

    public  $tipo, $search, $selected_id, $pageTitle, $componentName;
    private $pagination = 7;

    public function mount()
    {
        $this->pageTitle = 'Listado';
        $this->componentName = 'Formas de Pago';
    }

    public function paginationView()
    {
        return 'vendor.livewire.bootstrap';
    }

    public function render()
    {
        if(strlen($this->search) > 0)
            $data = TipoPagos::where('forma', 'like', '%' . $this->search . '%')->paginate($this->pagination);
        else
            $data = TipoPagos::orderBy('forma', 'asc')->paginate($this->pagination);

        return view('livewire.tipoPago.tipo-pagos', ['tipos' => $data])
        ->extends('layouts.theme.app')
        ->section('content');
    }

    public function Store()
    {
        $rules = [
            'tipo' => 'required'
        ];

        $messages = [
            'tipo.required' => 'Nombre de la Forma de pago es requerido',
        ];

        $this->validate($rules, $messages);

        $family = TipoPagos::create(['forma' => $this->tipo]);

        $this->resetUI();
        $this->emit('item-added', 'Forma de pago registrada');
    }

    public function Edit($id)
    {
        $record = TipoPagos::find($id);
        $this->tipo = $record->forma;
        $this->selected_id = $record->id;

        $this->emit('show-modal', 'show modal');
    }

    public function resetUI()
    {
        $this->tipo = '';
        $this->search = '';
        $this->selected_id = 0;
        $this->resetValidation();
        $this->resetPage();
    }

    public function Update()
    {
        $rules = [
            'tipo' => 'required'
        ];

        $messages = [
            'tipo.required' => 'Nombre de la Forma de Pago es requerido',
        ];

        $this->validate($rules, $messages);

        $tipos = TipoPagos::find($this->selected_id);
        $tipos->update([
            'forma' => $this->tipo
        ]);
        $this->resetUI();
        $this->emit('item-updated', 'Forma de pago Actualizada');
    }

    protected $listeners = [
        'deleteRow' => 'Destroy'
    ];

    public function Destroy(TipoPagos $tipos /*$id*/)
    {
        $tipos->delete();

        $this->resetUI();
        $this->emit('item-deleted', 'Forma de Pagos Eliminada');
    }
}
