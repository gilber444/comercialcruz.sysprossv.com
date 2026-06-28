<?php

namespace App\Http\Livewire;

use App\Models\Feature;
use Livewire\Component;
use Livewire\WithPagination;

class FeaturesController extends Component
{
    use WithPagination;

    public $search = '';
    public $pageTitle = 'Control de Versiones';
    public $componentName = 'Features';

    private $pagination = 15;

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
        $features = Feature::where('descripcion', 'like', '%' . $this->search . '%')
            ->orWhere('version', 'like', '%' . $this->search . '%')
            ->orderByDesc('id')
            ->paginate($this->pagination);

        return view('livewire.features.features', ['features' => $features])
            ->extends('layouts.theme.app')
            ->section('content');
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
    }
}
