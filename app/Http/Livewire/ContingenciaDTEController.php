<?php

namespace App\Http\Livewire;

use App\Models\AmbienteDestino;
use App\Models\ContingenciaDte;
use App\Models\ContingenciadteDetalles;
use App\Models\dte;
use App\Models\Empresas;
use App\Models\TipoContigencia;
use App\Traits\Firmador;
use App\Traits\Firmador2;
use App\Traits\GenerarJsonContingencia;
use App\Traits\RecepcionContingencia;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithPagination;
use PDOException;

class ContingenciaDTEController extends Component
{
    use WithPagination;
    use GenerarJsonContingencia;
    use Firmador;
    use RecepcionContingencia;
    use Firmador2;

    public $search,
        $selected_id,
        $pageTitle,
        $componentName,
        $tipos,
        $tipo,
        $otro,
        $selectedDtes = [],
        $selectAll = false,
        $dtes,
        $fInicio,
        $hInicio,
        $fFin,
        $hFin,
        $detalle;
    private $pagination = 7;

    public function mount()
    {
        $user = Auth::user();

        $this->pageTitle = 'Listado';
        $this->componentName = 'Contingencias DTE';
        $this->tipos = TipoContigencia::all();

        $this->dtes = dte::where(function ($query) {
            $query->where('estado', 'Creado')
                  ->orWhere('estado', 'Firmado');
        })
        ->where('empresa', $user->empresa) // Nueva condición where
        ->orderBy('numeroControl', 'asc')
        ->get();
    }

    public function paginationView()
    {
        return 'vendor.livewire.bootstrap';
    }

    public function render()
    {
        $user = Auth::user();
        $data = ContingenciaDte::withCount('contingenciaDteDetalles')
        ->where('empresa', $user->empresa)
        ->when(strlen($this->search) > 0, function ($query) {
            $query->where(function ($subQuery) {
                $subQuery->where('fTransmision', $this->search)
                    ->orWhere('codigoGeneracion', $this->search)
                    ->orWhere('estado', $this->search);
            });
        })
        ->orderByDesc('fTransmision', 'DESC')
        ->orderByDesc('hTransmision', 'DESC')
        ->paginate($this->pagination);


        return view('livewire.dtes.contingencia-d-t-e', ['data' => $data])
            ->extends('layouts.theme.app')
            ->section('content');
    }

    public function updatedSelectAll($value)
    {
        if ($value) {
            $this->selectedDtes = $this->dtes->pluck('id')->toArray();
        } else {
            $this->selectedDtes = [];
        }
    }

    public function resetUI()
    {
        $this->search = '';
        $this->selected_id = '';
        $this->tipo = '';
        $this->otro = '';
        $this->selectedDtes = [];
        $this->selectAll = false;
        $this->fInicio = '';
        $this->hInicio = '';
        $this->fFin = '';
        $this->hFin = '';
    }
    public $listeners = [
        'Store' => 'Store',
    ];

    public function Store()
    {
        $rules = [
            'tipo' => 'required|not_in:elegir',
            'selectedDtes' => 'required|array|min:1', // Se requiere que sea un array con al menos un elemento
        ];

        $messages = [
            'selectedDtes.required' => 'Es obligatorio seleccionar al menos un DTE para realizar la contingencia.',
            'selectedDtes.array' => 'El campo selectedDtes debe ser un arreglo.',
            'selectedDtes.min' => 'Es obligatorio seleccionar al menos un DTE para realizar la contingencia.',
        ];

        $this->validate($rules, $messages);

        $user = Auth::user();
        $empresa = Empresas::find($user->empresa);
        try {
            DB::beginTransaction();
            $ambiente = AmbienteDestino::find($empresa->ambiente);

            $ultimoNumero = ContingenciaDte::where('empresa', $user->empresa)->orderBy('numero', 'desc')->value('numero');

            $ultimoNumero = ContingenciaDte::where('empresa', $user->empresa)->orderBy('numero', 'desc')->value('numero');
            $numero = $ultimoNumero !== null ? $ultimoNumero + 1 : 1;

            $contin = ContingenciaDte::create([
                'numero' => $numero,
                'version' => 3,
                'ambiente' => $ambiente->id,
                'codigoGeneracion' => strtoupper(Str::uuid()->toString()),
                'fTransmision' => date('Y-m-d'),
                'hTransmision' => date('H:i:s'),
                'emisor' => $user->empresa,
                'empresa' => $user->empresa,
                'fInicio' => $this->fInicio,
                'fFin' => $this->fFin,
                'hInicio' => $this->hInicio,
                'hFin' => $this->hFin,
                'tipoContingencia' => $this->tipo,
                'motivoContingencia' => $this->otro,
                'json' => null,
                'jsonFirmado' => null,
                'estado' => 'Creado',
                'fechaHora' => null,
                'mensaje' => null,
                'selloRecibido' => null,
                'observaciones' => null,
                'jsonRespuesta' => null,
            ]);

            foreach ($this->selectedDtes as $index => $dteId) {
                $estado = dte::find($dteId)->estado;
                $detalle = ContingenciadteDetalles::create([
                    'contingencia' => $contin->id,
                    'noItem' => $index + 1,
                    'dte' => $dteId,
                    'estado' => $estado,
                ]);
            }

            DB::commit();
            $result = $this->GenerarJsonContingencia($contin->id);
            $firmador = $this->Firmador2($result);
            $contin->jsonFirmado = $firmador;
            $contin->estado ='Firmado';
            $contin->save();
            $this->RecepcionContingencia($contin->id);

            $this->emit('item-added', 'Contingencia Realizada y Procesada');
            $this->resetUI();
        } catch (Exception $e) {
            DB::rollBack();
            $this->emit('item-error', 'Error al realizar la contingencia: ' . $e->getMessage());
        }
    }

    public function Detalle($id)
    {
        $this->detalle = ContingenciaDteDetalles::join('dtes', 'contingenciadte_detalles.dte', 'dtes.id')
        ->select('contingenciadte_detalles.id', 'contingenciadte_detalles.dte', 'contingenciadte_detalles.noItem', 'dtes.numeroControl', 'dtes.codigoGeneracion', 'dtes.fecEmi', 'dtes.estado')
        ->where('contingencia', $id)
        ->get();
        $this->emit('sshoww-modal', 'show modal');
    }

    public function Procesar($id)
    {
        $json = $this->GenerarJsonContingencia($id);
        $firm = $this->Firmador2($json);

        $con = ContingenciaDte::find($id);
        $con->jsonFirmado = $firm;
        $con->save();

        $this->RecepcionContingencia($id);
    }
}
