<?php

namespace App\Http\Livewire;

use App\Models\Remesas;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class RemesasController extends Component
{
    use WithPagination;

    public $search, $selected_id, $pageTitle, $componentName, $pagination = 10;

    public function mount()
    {
        $this->pageTitle = 'Listado';
        $this->componentName = 'Remesas realizadas';
    }

    public function paginationView()
    {
        return 'vendor.livewire.bootstrap';
    }

    public function render()
    {
        $user = Auth::user();

        $query = Remesas::with([
            'Rempresas:id,empresa',
            'Rsucursales:id,nombre',
            'Rcajas:id,caja',
            'Rcajeros:id,name'
        ]);

        if (!in_array($user->profile, ['Super', 'Administrador', 'Gerente'])) {
        $query->where('sucursal', $user->sucursal);
        }

        $search = trim($this->search);

        // Intentar parsear "d/m/Y" -> "Y-m-d"
        $searchDateYmd = null;
        if ($search !== '' && preg_match('/^\d{1,2}\/\d{1,2}\/\d{4}$/', $search)) {
            try {
                $searchDateYmd = Carbon::createFromFormat('d/m/Y', $search)->format('Y-m-d');
            } catch (\Throwable $e) {
                $searchDateYmd = null;
            }
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search, $searchDateYmd) {
                $q->where('numero', 'like', "%{$search}%")
                ->orWhere('monto', 'like', "%{$search}%")
                ->orWhere('hora', 'like', "%{$search}%")
                // match exact día si se ingresó en d/m/Y
                ->when($searchDateYmd, fn($qq) => $qq->orWhereDate('fecha', $searchDateYmd))
                // permite búsquedas parciales en el formato mostrado (d/m/Y)
                ->orWhereRaw("DATE_FORMAT(fecha, '%d/%m/%Y') LIKE ?", ["%{$search}%"])
                // relaciones
                ->orWhereRelation('Rempresas', 'empresa', 'like', "%{$search}%")
                ->orWhereRelation('Rsucursales', 'nombre', 'like', "%{$search}%")
                ->orWhereRelation('Rcajas', 'caja', 'like', "%{$search}%")
                ->orWhereRelation('Rcajeros', 'name', 'like', "%{$search}%");
            });
        }

        $data = $query->orderBy('fecha', 'desc')
                        ->orderBy('hora', 'desc')
                        ->paginate($this->pagination);

        return view('livewire.remesas.remesas', [
            'remesas' => $data
        ])
        ->extends('layouts.theme.app')
        ->section('content');
    }

    protected $listeners = [
        'deleteRow' => 'Destroy'
    ];

    public function Destroy($id)
    {
        $remesa = Remesas::find($id);
        $remesa->delete();

        //$this->resetUI();
        $this->emit('item-deleted', 'Remesa Eliminada');
    }
}
