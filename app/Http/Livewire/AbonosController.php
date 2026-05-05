<?php

namespace App\Http\Livewire;

use App\Models\Abonos;
use Livewire\Component;
use Livewire\WithPagination;

class AbonosController extends Component
{
    use WithPagination;

    public  $search, $selected_id, $pageTitle, $componentName;
    private $pagination = 7;

    public function mount()
    {
        $this->pageTitle = 'Listado';
        $this->componentName = 'Abonos realizados de Clientes';
    }

    public function paginationView()
    {
        return 'vendor.livewire.bootstrap';
    }


    public function render()
    {
        $data = Abonos::with([
            'Rcreditos' => function ($query) {
                $query->with([
                    'Rclientes',
                    'Rventas' => function ($query) {
                        //$query->with(['RtipoCompra']);
                    },
                ]);
            },
            'Rusers',
        ])
        /*->when($this->search, function ($query) {
            $query->where(function ($query) {
                $query->where('correlativo', 'like', '%' . $this->search . '%') // Buscar por correlativo
                    ->orWhereHas('RcuentaPagar.Rproveedores', function ($q) {
                        $q->where('nombre', 'like', '%' . $this->search . '%'); // Buscar por nombre del proveedor
                    })
                    ->orWhereHas('RcuentaPagar.Rcompras.RtipoCompra', function ($q) {
                        $q->where('tipo', 'like', '%' . $this->search . '%'); // Buscar por tipo de factura
                    })
                    ->orWhereHas('RtipoPago', function ($q) {
                        $q->where('forma', 'like', '%' . $this->search . '%'); // Buscar por método de pago
                    })
                    ->orWhereHas('Ruser', function ($q) {
                        $q->where('name', 'like', '%' . $this->search . '%');
                    });// Buscar por usuario


                  // Validar si el término de búsqueda tiene el formato d/m/Y
                  if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $this->search)) {
                      $query->orWhereDate('fecha', Carbon::createFromFormat('d/m/Y', $this->search)->format('Y-m-d'));
                  }
              });
        }) */
        ->orderBy('id', 'desc')
        ->paginate($this->pagination);

        return view('livewire.cuentas_cobrar.abonos', ['data' => $data])
        ->extends('layouts.theme.app')
        ->section('content');
    }
}
