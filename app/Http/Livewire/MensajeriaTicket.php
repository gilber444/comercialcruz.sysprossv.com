<?php

namespace App\Http\Livewire;

use App\Models\MensajeriaTicket as ModelsMensajeriaTicket;
use App\Models\Empresas;
use Livewire\Component;
use Livewire\WithPagination;

class MensajeriaTicket extends Component
{
    use WithPagination;

    public  $lema, $mensaje, $aviso, $notificacion, $empresa, $search, $selected_id, $pageTitle, $componentName, $empresas = [];
    private $pagination = 7;

    public function mount()
    {
        $this->pageTitle = 'Listado';
        $this->componentName = 'Mensajeria para impresion de ticket';

        $this->empresas= Empresas::all();
    }

    public function paginationView()
    {
        return 'vendor.livewire.bootstrap';
    }

    public function render()
    {
       if(strlen($this->search) > 0)
            $data = ModelsMensajeriaTicket::where('lema', 'like', '%' . $this->search . '%')->paginate($this->pagination);
        else
            $data = ModelsMensajeriaTicket::orderBy('lema', 'asc')->paginate($this->pagination);

        return view('livewire.mensajeria.mensajeria-ticket', ['mensajerias' => $data])
        ->extends('layouts.theme.app')
        ->section('content');
    }

    public function Store()
    {
        $rules = [
            'empresa' => 'required',
        ];

        $messages = [
            'empresa.required' => 'Seleccionar la empresa es requerido',
        ];

        $this->validate($rules, $messages);

        $category = ModelsMensajeriaTicket::create([
            'lema' => $this->lema,
            'mensaje' => $this->mensaje,
            'aviso' => $this->aviso,
            'notificacion' => $this->notificacion,
            'empresa' => $this->empresa

        ]);

        $this->resetUI();
        $this->emit('item-added', 'Mensajeria registrada');
    }

    public function Edit($id)
    {
        $record = ModelsMensajeriaTicket::find($id);
        $this->lema = $record->lema;
        $this->mensaje = $record->mensaje;
        $this->aviso = $record->aviso;
        $this->notificacion = $record->notificacion;
        $this->empresa = $record->empresa;
        $this->selected_id = $record->id;

        $this->emit('show-modal', 'show modal');
    }

    public function resetUI()
    {
        $this->lema = '';
        $this->mensaje = '';
        $this->aviso = '';
        $this->notificacion = '';
        $this->empresa = '';
        $this->search = '';
        $this->selected_id = 0;
        $this->resetValidation();
        $this->resetPage();
    }

    public function Update()
    {
        $rules = [
            'empresa' => 'required',
        ];

        $messages = [
            'empresa.required' => 'Seleccionar la empresa es requerido',
        ];

        $this->validate($rules, $messages);

        $category = ModelsMensajeriaTicket::find($this->selected_id);
        $category->update([
            'lema' => $this->lema,
            'mensaje' => $this->mensaje,
            'aviso' => $this->aviso,
            'notificacion' => $this->notificacion,
            'empresa' => $this->empresa
        ]);
        $this->resetUI();
        $this->emit('item-updated', 'Mensaje Actualizado');
    }

    protected $listeners = [
        'deleteRow' => 'Destroy'
    ];

    public function Destroy(ModelsMensajeriaTicket $category /*$id*/)
    {
        $category->delete();

        $this->resetUI();
        $this->emit('item-deleted', 'Mensaje Eliminado');
    }
}

