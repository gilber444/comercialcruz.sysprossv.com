<?php

namespace App\Http\Livewire;

use App\Models\MetasUsers as ModelsMetasUsers;
use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;

class MetasUsers extends Component
{
    use WithPagination;

    public  $usuario, $usuarios, $search, $selected_id, $pageTitle, $componentName, $fecha_inicio, $fecha_fin, $meta;
    private $pagination = 7;

    public function mount()
    {
        $this->pageTitle = 'Listado';
        $this->componentName = 'Metas de ventas por usuarios';

        $this->usuarios = User::where('profile', '<>', 'Super')->where('status', 'ACTIVE')->get();
    }

    public function paginationView()
    {
        return 'vendor.livewire.bootstrap';
    }

    public function render()
    {
        $query = ModelsMetasUsers::with('Rusers')->orderBy('fecha_inicio', 'desc');

        if (!empty($this->search)) {
            $query->where(function ($q) {
                $q->where('fecha_inicio', 'like', '%' . $this->search . '%')
                ->orWhereHas('Rusers', function ($userQuery) {
                    $userQuery->where('name', 'like', '%' . $this->search . '%');
                });
            });
        }

        $data = $query->paginate($this->pagination);


        return view('livewire.metas.metas-users', ['data' => $data])
        ->extends('layouts.theme.app')
        ->section('content');
    }

    public function Edit($id)
    {
        $record = ModelsMetasUsers::find($id);
        $this->selected_id = $record->id;
        $this->usuario = $record->user;
        $this->meta = $record->meta;
        $this->fecha_inicio = $record->fecha_inicio;
        $this->fecha_fin = $record->fecha_fin;

        $this->emit('show-modal', 'show-modal');
    }

    public function Store()
    {
        $rule = [
            'usuario' => 'required',
            'meta' => 'required|numeric|decimal:0,2', // Acepta enteros y hasta 2 decimales
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date'
        ];

        $message = [
            'usuario.required' => 'Seleccione un usuario',
            'meta.required' => 'Ingrese la meta',
            'meta.numeric' => 'La meta debe ser un número',
            'meta.decimal' => 'La meta debe tener hasta 2 decimales',
            'fecha_inicio.required' => 'Ingrese la fecha de inicio',
            'fecha_inicio.date' => 'La fecha de inicio debe ser válida',
            'fecha_fin.required' => 'Ingrese la fecha de fin',
            'fecha_fin.date' => 'La fecha de fin debe ser válida'
        ];

        $this->validate($rule, $message);

        $data = ModelsMetasUsers::create([
            'user' => $this->usuario,
            'meta' => $this->meta,
            'fecha_inicio' => $this->fecha_inicio,
            'fecha_fin' => $this->fecha_fin,
            'estado' => 'Activo'
        ]);

        $this->resetUI();
        $this->emit('item-added', 'Registro guardado');
    }

    public function resetUI()
    {
        $this->usuario = '';
        $this->meta = '';
        $this->fecha_inicio = '';
        $this->fecha_fin = '';
        $this->selected_id = 0;
        $this->search = '';
        $this->resetValidation();
        $this->resetPage();
    }

    public function Update()
    {
        $rule = [
            'usuario' => 'required',
            'meta' => 'required|numeric|decimal:0,2', // Acepta enteros y hasta 2 decimales
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date'
        ];

        $message = [
            'usuario.required' => 'Seleccione un usuario',
            'meta.required' => 'Ingrese la meta',
            'meta.numeric' => 'La meta debe ser un número',
            'meta.decimal' => 'La meta debe tener hasta 2 decimales',
            'fecha_inicio.required' => 'Ingrese la fecha de inicio',
            'fecha_inicio.date' => 'La fecha de inicio debe ser válida',
            'fecha_fin.required' => 'Ingrese la fecha de fin',
            'fecha_fin.date' => 'La fecha de fin debe ser válida'
        ];

        $this->validate($rule, $message);

        $data = ModelsMetasUsers::find($this->selected_id);
        $data->update([
            'user' => $this->usuario,
            'meta' => $this->meta,
            'fecha_inicio' => $this->fecha_inicio,
            'fecha_fin' => $this->fecha_fin
        ]);

        $this->resetUI();
        $this->emit('item-updated', 'Registro actualizado');
    }

    protected $listeners = [
        'deleteRow' => 'Destroy'
    ];

    public function Destroy($id)
    {
        $data = ModelsMetasUsers::find($id);
        $data->delete();
        $this->resetUI();
        $this->emit('item-deleted', 'Registro eliminado');
    }
}
