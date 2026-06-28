<?php

namespace App\Http\Livewire;

use App\Models\RetencionesRenta as ModelsRetencionesRenta;
use Livewire\Component;
use Livewire\WithPagination;

class RetencionesRenta extends Component
{
    public $search, $records, $selected_id,$pageTitle, $modalAction, $componentName,$codigo,
    $valor, $status, $pagination = 10, $tipopago, $retenciondesde, $retencionhasta, $cuotafija, $porcentaje, $exceso;

    use WithPagination;

    public function mount()
    {
        $this->pageTitle = 'Listado';
        $this->componentName = 'Retenciones Renta';
    }

    public function paginationView()
    {
        return 'vendor.livewire.bootstrap';
    }

    public function render()
    {
        return view('livewire.retenciones_renta.retenciones_renta',[
            'retenciones' => $this->Allretenciones()
        ])
        ->extends('layouts.theme.app')
        ->section('content');
    }

    public function Allretenciones()
    {
        if (!empty($this->search)) {

            $this->resetPage();

            $query = ModelsRetencionesRenta::where('tipopago', 'like', "%{$this->search}%")
                ->orderBy('created_at', 'asc');
        } else {
            $query = ModelsRetencionesRenta::orderBy('created_at', 'desc');
        }

        $this->records = $query->count();

        return $query->paginate($this->pagination);
    }

    protected function rules()
    {
        $rules = [
            'tipopago' => "required|min:1",
            'porcentaje' => "required|min:1",
            'retenciondesde' => "required|numeric|min:0",
            'retencionhasta' => "required|numeric|min:0",
            'cuotafija' => "required|numeric|min:0"
        ];


        return $rules;
    }

    protected function messages()
    {
        return [
            'tipopago.required' => 'El tipo de pago es requerida',
            'tipopago.min'=> 'El tipo de pago debe tener mas de 1 caracteres',
            'porcentaje.required' => 'El porcentaje es requerida',
            'porcentaje.min'=> 'El porcenataje debe tener mas de 1 caracteres',
            'retenciondesde.required' => 'El campo "Retención desde" es requerido',
            'retenciondesde.numeric' => 'El campo "Retención desde" debe ser un número válido',
            'retenciondesde.max_digits' => 'El campo "Retención desde" no debe exceder 10 dígitos',
            'retencionhasta.required' => 'El campo "Retención hasta" es requerido',
            'retencionhasta.numeric' => 'El campo "Retención hasta" debe ser un número válido',
            'retencionhasta.max_digits' => 'El campo "Retención hasta" no debe exceder 10 dígitos',
            'cuotafija.required' => 'El campo "Cuota fija" es requerido',
            'cuotafija.numeric' => 'El campo "Cuota fija" debe ser un número válido'
        ];
    }


    public function Store()
    {
        $this->validate($this->rules(), $this->messages());

        $createRetencion = ModelsRetencionesRenta::create([
            'tipopago' => $this->tipopago,
            'retenciondesde' => $this->retenciondesde,
            'retencionhasta' => $this->retencionhasta,
            'cuotafija' => $this->cuotafija,
            'porcentaje' => $this->porcentaje,
            'exceso' => $this->exceso
        ]);

        $this->emit('item-added', 'Retencion registrado con exito');
        $this->ResetInt();
    }

    public function Edit($id)
    {

        $selectRetencion = ModelsRetencionesRenta::find($id);
        $this->tipopago = $selectRetencion->tipopago;
        $this->retenciondesde = $selectRetencion->retenciondesde;
        $this->retencionhasta = $selectRetencion->retencionhasta;
        $this->cuotafija = $selectRetencion->cuotafija;
        $this->porcentaje = $selectRetencion->porcentaje;
        $this->exceso = $selectRetencion->exceso;
        $this->selected_id = $selectRetencion->id;

        $this->emit('show-modal','Editar Retencion');
    }

    public function Update()
    {
        $this->validate($this->rules(), $this->messages());

        $updateRetencion = ModelsRetencionesRenta::find($this->selected_id);
        $updateRetencion->tipopago = $this->tipopago;
        $updateRetencion->retenciondesde = $this->retenciondesde;
        $updateRetencion->retencionhasta = $this->retencionhasta;
        $updateRetencion->cuotafija = $this->cuotafija;
        $updateRetencion->porcentaje = $this->porcentaje;
        $updateRetencion->exceso = $this->exceso;


        $updateRetencion->save();

        $this->emit('item-updated', 'Retencion Actualizado con exito');
        $this->ResetInt();
    }

    protected $listeners = [
        'deleteRow' => 'destroy'
    ];

    public function destroy($id)
    {
        $deleteRetencion = ModelsRetencionesRenta::findOrFail($id);
        $deleteRetencion->delete();

        $this->ResetInt();
        $this->emit('item-updated', 'RETENCION ELIMINADO CON ÉXITO');
    }

    public function ResetInt()
    {
        $this->tipopago = '';
        $this->retenciondesde = '';
        $this->retencionhasta = '';
        $this->cuotafija = '';
        $this->porcentaje = '';
        $this->exceso = '';
        $this->selected_id = 0;
        $this->resetValidation();
        $this->resetPage();
    }
}
