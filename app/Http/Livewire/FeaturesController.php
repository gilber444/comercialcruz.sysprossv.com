<?php

namespace App\Http\Livewire;

use App\Models\Feature;
use Livewire\Component;
use Livewire\WithPagination;

class FeaturesController extends Component
{
    use WithPagination;

    public $search = '';
    public $filterStatus = 'all';
    public $pageTitle = 'Control de Versiones';
    public $componentName = 'Features';

    public $version = '';
    public $descripcion = '';
    public $selected_id = 0;

    private $pagination = 12;

    protected $listeners = [
        'destroy' => 'Destroy',
    ];

    protected $rules = [
        'version'     => 'required|max:20',
        'descripcion' => 'required|max:500',
    ];

    protected $messages = [
        'version.required'     => 'La versión es obligatoria.',
        'descripcion.required' => 'La descripción es obligatoria.',
    ];

    public function paginationView()
    {
        return 'vendor.livewire.bootstrap';
    }

    public function mount()
    {
        $this->pageTitle = 'Listado';
        $this->componentName = 'Features / Versiones';
    }

    public function render()
    {
        $query = Feature::query()
            ->where(function ($q) {
                $q->where('descripcion', 'like', '%' . $this->search . '%')
                  ->orWhere('version', 'like', '%' . $this->search . '%');
            });

        switch ($this->filterStatus) {
            case 'active':
                $query->where('activo', true);
                break;
            case 'inactive':
                $query->where('activo', false);
                break;
            case 'tests':
                $query->where('produccion', false);
                break;
            case 'released':
                $query->where('produccion', true);
                break;
        }

        $features = $query->orderByDesc('id')->paginate($this->pagination);

        return view('livewire.features.features', [
            'features' => $features,
            'counts' => [
                'all'      => Feature::count(),
                'active'   => Feature::where('activo', true)->count(),
                'inactive' => Feature::where('activo', false)->count(),
                'tests'    => Feature::where('produccion', false)->count(),
                'released' => Feature::where('produccion', true)->count(),
            ],
        ])
            ->extends('layouts.theme.app')
            ->section('content');
    }

    public function Store()
    {
        $this->validate();

        Feature::create([
            'version'     => $this->version,
            'descripcion' => $this->descripcion,
            'activo'      => true,
            'produccion'  => false,
        ]);

        $this->resetUI();
        $this->emit('noty', ['msg' => 'Versión registrada correctamente.', 'title' => 'OK', 'type' => 'success']);
        $this->emit('close-modal', 'modalFeature');
    }

    public function Edit($id)
    {
        $feature = Feature::findOrFail($id);
        $this->selected_id = $feature->id;
        $this->version     = $feature->version;
        $this->descripcion = $feature->descripcion;
        $this->emit('show-modal', 'modalFeature');
    }

    public function Update()
    {
        $this->validate([
            'version'     => 'required|max:20',
            'descripcion' => 'required|max:500',
        ]);

        $feature = Feature::findOrFail($this->selected_id);
        $feature->update([
            'version'     => $this->version,
            'descripcion' => $this->descripcion,
        ]);

        $this->resetUI();
        $this->emit('noty', ['msg' => 'Versión actualizada correctamente.', 'title' => 'OK', 'type' => 'success']);
        $this->emit('close-modal', 'modalFeature');
    }

    public function Destroy($id)
    {
        Feature::findOrFail($id)->delete();
        $this->resetUI();
        $this->emit('noty', ['msg' => 'Versión eliminada.', 'title' => 'OK', 'type' => 'success']);
    }

    public function toggleActivo($id)
    {
        $feature = Feature::findOrFail($id);
        $feature->activo = !$feature->activo;
        $feature->save();

        $this->emit('noty', ['msg' => "Feature {$feature->version} actualizado", 'title' => 'OK', 'type' => 'success']);
    }

    public function toggleProduccion($id)
    {
        $feature = Feature::findOrFail($id);
        $feature->produccion = !$feature->produccion;
        $feature->save();

        $this->emit('noty', ['msg' => "Feature {$feature->version} " . ($feature->produccion ? 'liberado a produccion' : 'pasado a pruebas'), 'title' => 'OK', 'type' => 'success']);
    }

    public function resetUI()
    {
        $this->resetPage();
        $this->search = '';
        $this->version = '';
        $this->descripcion = '';
        $this->selected_id = 0;
        $this->resetValidation();
    }
}
