<?php

namespace App\Http\Livewire;

use App\Models\Pagos;
use Carbon\Carbon;
use Livewire\Component;
use Livewire\WithPagination;

class PagosController extends Component
{
    use WithPagination;

    public  $search, $selected_id, $pageTitle, $componentName;
    private $pagination = 7;

    public function mount()
    {
        $this->pageTitle = 'Listado';
        $this->componentName = 'Pagos realizado a Proveedores';
    }

    public function paginationView()
    {
        return 'vendor.livewire.bootstrap';
    }


    public function render()
    {
        $data = Pagos::with([
            'RcuentaPagar' => function ($query) {
                $query->with([
                    'Rproveedores',
                    'Rcompras' => function ($query) {
                        $query->with(['RtipoCompra']);
                    },
                ]);
            },
            'Ruser',
            'RtipoPago',
        ])
        ->when($this->search, function ($query) {
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
        })
        ->orderBy('id', 'desc')
        ->paginate($this->pagination);

        //dd($data);
        return view('livewire.cuentas_pagar.pagos', ['data' => $data])
            ->extends('layouts.theme.app')
            ->section('content');
    }

}
