<?php

namespace App\Http\Livewire;

use App\Models\AmbienteDestino;
use App\Models\dte;
use App\Models\Empresas;
use App\Models\lotedte;
use App\Models\lotedteDetalles;
use App\Traits\ConsultaLote;
use App\Traits\RecepcionLote;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithPagination;

class LotedteController extends Component
{
    use WithPagination;
    use RecepcionLote;
    use ConsultaLote;

    public $search, $pageTitle, $componentName, $selected_id, $disponibles, $cant, $detalle;

    private $pagination = 7;

    public function mount()
    {
        $this->pageTitle = 'Listado';
        $this->componentName = 'Envio de DTES por Lote';
        $this->disponibles = dte::where('fecEmi', date('Y-m-d'))->whereIn('estado', ['Firmado', 'Creado'])->where('sucursal', 2)->count();
    }

    public function paginationView()
    {
        return 'vendor.livewire.bootstrap';
    }

    public function render()
    {
        if (strlen($this->search) > 0) {
            $data = lotedte::withCount('lotedte_detalles')
                ->where('numero', $this->search)
                ->orWhere('fecha', $this->search)
                ->orWhere('idEnvio', $this->search)
                ->paginate($this->pagination);
        } else {
            $data = lotedte::withCount('lotedte_detalles')
                ->orderBy('numero', 'asc')
                ->paginate($this->pagination);
        }

        return view('livewire.dtes.lotedte', ['lotes' => $data])
            ->extends('layouts.theme.app')
            ->section('content');
    }

    public function Store()
    {
        $rules = [
            'cant' => 'required|numeric|between:2,100',
        ];

        $messages = [
            'cant.required' => 'La cantidad es requerida',
            'cant.numeric' => 'La cantidad debe ser un valor numérico',
            'cant.between' => 'La cantidad debe estar entre 2 y 100',
        ];

        $this->validate($rules, $messages);

        $fechaHoy = Carbon::now()->toDateString();

        $user = Auth::user();

        // Realizar la consulta a la tabla 'dtes'
        $dtes = dte::where('fecEmi', '2025-03-01')
            ->where('sucursal', 3)
            ->where('estado', 'Creado')
            ->orderBy('id')
            ->take($this->cant)
            ->get();

        if ($dtes->count() > 0) {

            $ultimoNumero = lotedte::orderBy('numero', 'desc')->value('numero');

            if ($ultimoNumero !== null) {
                // Si se encontró un número en la tabla
                $numero = $ultimoNumero + 1;
            } else {
                // Si no se encontró ningún número en la tabla
                $numero = 1;
            }

            $empresa = Empresas::find($user->empresa);

            $ambiente = AmbienteDestino::find($empresa->ambiente);

            $lote = lotedte::create([
                'numero' => $numero,
                'fecha' => date('Y-m-d'),
                'hora' => date('H:i:s'),
                'ambiente' => $ambiente->id,
                'idEnvio' => strtoupper(Str::uuid()->toString()),
                'version' => 2,
                'sucursal' => $user->sucursal,
                'empresa' => $user->empresa,
                'estado' => 'Creado',
                'fhProcesamiento' => NULL,
                'codigoLote' => NULL,
                'codigoMsg' => NULL,
                'descripcionMsg' => NULL,
                'json' => NULL,
                'jsonRespuesta' => NULL
            ]);
            foreach($dtes as $dt)
            {
                $detalle = lotedteDetalles::create([
                    'lote' => $lote->id,
                    'dte' => $dt->id,
                    'estado' => $dt->estado
                ]);
            }
            $this->RecepcionLote($lote->id);
            $this->ConsultaLote($lote->id);

            $this->emit('item-added', 'Lote enviado y procesado');
        } else {
            $this->emit('item-error', 'No hay DTES para procesar de este dia');
        }
    }

    public function resetUI()
    {
        $this->cant = '';
    }

    public function Detalle($id)
    {
        $this->detalle = $id;
        $this->emit('sshoww-modal', 'show modal');
    }
}
