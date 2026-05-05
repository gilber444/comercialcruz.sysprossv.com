<?php

namespace App\Http\Livewire;

use Carbon\Carbon;
use App\Models\dte;
use Livewire\Component;
use App\Models\Empresas;
use App\Traits\Firmador;
use App\Models\Parametros;
use App\Models\Sucursales;
use App\Traits\FirmadorDTE;
use Illuminate\Support\Str;
use Livewire\WithPagination;
use App\Models\AmbienteDestino;
use App\Models\InvalidacionDte;
use App\Traits\GenerarJsonInva;
use App\Models\TipoInvalidacion;
use App\Traits\Firmador2;
use Illuminate\Support\Facades\Auth;
use App\Traits\RecepcionInvalidacion;

class InvalidacionesDTEController extends Component
{
    use WithPagination;
    use GenerarJsonInva;
    use RecepcionInvalidacion;
    use Firmador;
    //use FirmadorDTE;
    use Firmador2;

    public $search, $selected_id, $pageTitle, $componentName, $numero, $tipo, $motivos, $motivo, $desmotivo, $dteinva, $dteid, $dterem, $estado, $detalle;
    private $pagination = 7;

    public function mount()
    {
        $user = Auth::user();
        $this->pageTitle = 'Listado';
        $this->componentName = 'DTE Invalidados';
        $this->motivos = TipoInvalidacion::all();

        $ultimoNumero = InvalidacionDte::where('empresa', $user->empresa)->orderBy('numero', 'desc')->value('numero');

        if ($ultimoNumero !== null) {
            // Si se encontró un número en la tabla
            $this->numero = $ultimoNumero + 1;
        } else {
            // Si no se encontró ningún número en la tabla
            $this->numero = 1;
        }
    }

    public function paginationView()
    {
        return 'vendor.livewire.bootstrap';
    }

    public function render()
    {
        $user = Auth::user();
        if (strlen($this->search) > 0) {
            $data = InvalidacionDte::where('codigoGeneracion', 'like', '%' . $this->search . '%')
                ->orWhere('fecAnula', 'like', '%' . $this->search . '%')
                ->orWhere('tipo', 'like', '%' . $this->search . '%')
                ->paginate($this->pagination)
                ->where('empresa', $user->empresa);
        } else {
            $data = InvalidacionDte::where('empresa', $user->empresa)->orderBy('numero', 'desc')->paginate($this->pagination);
        }

        return view('livewire.dtes.invalidaciones-d-t-e', ['invalidaciones' => $data])
            ->extends('layouts.theme.app')
            ->section('content');
    }

    public function cargarDatos()
    {
        $dte = dte::select('id', 'codigoGeneracion')
            ->where('codigoGeneracion', $this->dteinva)
            ->first();

        if ($dte) {
            $this->dteinva = $dte->codigoGeneracion;
            $this->dteid = $dte->id;
        } else {
            $this->dteinva = '';
            $this->dteid = '';
            $this->emit('item-error', 'DTE no Registrado');
        }
    }

    public function Save()
    {

        $rules = [
            'numero' => 'required',
            'tipo' => 'required|not_in:elegir',
            'motivo' => 'required|not_in:elegir',
            'dteinva' => 'required',
            'dteid' => 'required',
        ];

        $messages = [
            'numero.required' => 'El número correlativo es requerido.',
            'tipo.required' => 'El tipo de invalidación es requerido.',
            'tipo.not_in' => 'Seleccione otra opción diferente de "Elegir" para el tipo de invalidación.',
            'motivo.required' => 'El motivo de la invalidación es requerido.',
            'motivo.not_in' => 'Seleccione otra opción diferente de "Elegir" para el motivo de la invalidación.',
            'dteinva.required' => 'El código de generación a invalidar es requerido.',
            'dteid.required' => 'No ha seleccionado ningun DTE.',
        ];

        $this->validate($rules, $messages);

        $user = Auth::user();

        $empresa = Empresas::find($user->empresa);

        $dte = dte::find($this->dteid);
        $ambiente = AmbienteDestino::find($empresa->ambiente);

        $sucursal = Sucursales::find($dte->sucursal);
        $caja = Parametros::find($dte->caja);

        if ($this->tipo === 'DEVOLUCION' and $this->dterem == null) {
            $this->emit('item-error', 'No se puede hacer devolución sin Código de Generación a Reemplazar');
            //dd('No se puede hacer devolucion sin Codigo de Generacion a Reemplazar ');
        } else {
            if ($dte->tipoDte == 1) {
                // Fecha y hora de generación del DTE
                $fechaHoraGeneracion = Carbon::parse($dte->fecEmi . ' ' . $dte->horEmi);
                // Fecha límite de vigencia (3 meses después de la generación)
                $fechaLimiteVigencia = $fechaHoraGeneracion->addMonths(3)->endOfDay();
                // Fecha y hora actual
                $fechaHoraActual = Carbon::now();

                if ($fechaHoraActual <= $fechaLimiteVigencia) {
                    if ($this->tipo === 'ANULACION') {
                        if ($dte->sello != null) {
                            $invalida = InvalidacionDte::create([
                                'tipo' => $this->tipo,
                                'ambiente' => $ambiente->id,
                                'numero' => $this->numero,
                                'codigoGeneracion' => strtoupper(Str::uuid()->toString()),
                                'fecAnula' => date('Y-m-d'),
                                'horAnula' => date('H:i:s'),
                                'emisor' => $dte->emisor,
                                'dte' => $this->dteid,
                                'codigoGeneracionR' => null,
                                'tipoAnulacion' => $this->motivo,
                                'motivoAnulacion' => $this->desmotivo,
                                'responsable' => Auth::user()->id,
                                'solicita' => Auth::user()->id,
                                'caja' => $caja->id,
                                'sucursal' => $sucursal->id,
                                'empresa' => $empresa->id,
                                'estado' => 'Generado',
                                'selloRecibido' => null,
                                'fhProcesamiento' => null,
                                'descripcionMsg' => null,
                                'json' => null,
                                'jsonRespuesta' => null,
                                'docFirmado' => null,
                            ]);

                            $json = $this->generarJsonInva($invalida->id);
                            $firmador = $this->Firmador2($json);

                            $invalida->json = $json;
                            $invalida->docFirmado = $firmador;
                            $invalida->save();

                            $recep = $this->RecepcionInvalidacion($invalida->id);

                            if ($recep == 'RECHAZADO') {
                                $this->ResetUI();
                                $this->emit('item-error', $recep);
                            } else {
                                $dte = dte::find($invalida->dte);
                                $dte->estado = 'INVALIDADO';
                                $dte->save();
                                $this->ResetUI();
                                $this->emit('item-added', $recep);
                            }
                        } else {
                            $this->emit('item-error', 'Este DTE necesita tener Sello de Recepcion para poder ser Anulado');
                        }
                    } else {
                        if ($dte->sello != null) {
                            $invalida = InvalidacionDte::create([
                                'tipo' => $this->tipo,
                                'ambiente' => $ambiente->id,
                                'numero' => $this->numero,
                                'codigoGeneracion' => strtoupper(Str::uuid()->toString()),
                                'fecAnula' => date('Y-m-d'),
                                'horAnula' => date('H:i:s'),
                                'emisor' => $dte->emisor,
                                'dte' => $this->dteid,
                                'codigoGeneracionR' => $this->dterem,
                                'tipoAnulacion' => $this->motivo,
                                'motivoAnulacion' => $this->desmotivo,
                                'responsable' => Auth::user()->id,
                                'solicita' => Auth::user()->id,
                                'caja' => $caja->id,
                                'sucursal' => $sucursal->id,
                                'empresa' => $empresa->id,
                                'estado' => 'Generado',
                                'selloRecibido' => null,
                                'fhProcesamiento' => null,
                                'descripcionMsg' => null,
                                'json' => null,
                                'jsonRespuesta' => null,
                                'docFirmado' => null,
                            ]);

                            $json = $this->generarJsonInva($invalida->id);
                            $firmador = $this->Firmador2($json);

                            $invalida->json = $json;
                            $invalida->docFirmado = $firmador;
                            $invalida->save();

                            $recep = $this->RecepcionInvalidacion($invalida->id);

                            if ($recep == 'RECHAZADO') {
                                $this->ResetUI();
                                $this->emit('item-error', $recep);
                            } else {
                                $dte = dte::find($invalida->dte);
                                $dte->estado = 'INVALIDADO';
                                $dte->save();
                                $this->ResetUI();
                                $this->emit('item-added', $recep);
                            }
                        } else {
                            $this->emit('item-error', 'Este DTE necesita tener Sello de Recepcion para poder ser Anulado');
                        }
                    }
                } else {
                    $this->emit('item-error', 'El registro ha superado el período de vigencia.');
                }
            } elseif ($dte->tipoDte == 2) {
                // Fecha y hora de generación del DTE
                $fechaHoraGeneracion = Carbon::parse($dte->fecEmi . ' ' . $dte->horEmi);
                // Fecha límite de vigencia (final del día siguiente)
                $fechaLimiteVigencia = $fechaHoraGeneracion->addDays(1)->endOfDay();
                // Fecha y hora actual
                $fechaHoraActual = Carbon::now();

                if ($fechaHoraActual <= $fechaLimiteVigencia) {
                    if ($this->tipo === 'ANULACION') {
                        if ($dte->sello != null) {
                            $invalida = InvalidacionDte::create([
                                'tipo' => $this->tipo,
                                'ambiente' => $ambiente->id,
                                'numero' => $this->numero,
                                'codigoGeneracion' => strtoupper(Str::uuid()->toString()),
                                'fecAnula' => date('Y-m-d'),
                                'horAnula' => date('H:i:s'),
                                'emisor' => $dte->emisor,
                                'dte' => $this->dteid,
                                'codigoGeneracionR' => null,
                                'tipoAnulacion' => $this->motivo,
                                'motivoAnulacion' => $this->desmotivo,
                                'responsable' => Auth::user()->id,
                                'solicita' => Auth::user()->id,
                                'caja' => $caja->id,
                                'sucursal' => $sucursal->id,
                                'empresa' => $empresa->id,
                                'estado' => 'Generado',
                                'selloRecibido' => null,
                                'fhProcesamiento' => null,
                                'descripcionMsg' => null,
                                'json' => null,
                                'jsonRespuesta' => null,
                                'docFirmado' => null,
                            ]);

                            $json = $this->generarJsonInva($invalida->id);
                            $firmador = $this->Firmador2($json);

                            $invalida->json = $json;
                            $invalida->docFirmado = $firmador;
                            $invalida->save();

                            $recep = $this->RecepcionInvalidacion($invalida->id);

                            if ($recep == 'RECHAZADO') {
                                $this->ResetUI();
                                $this->emit('item-error', $recep);
                            } else {
                                $dte = dte::find($invalida->dte);
                                $dte->estado = 'INVALIDADO';
                                $dte->save();
                                $this->ResetUI();
                                $this->emit('item-added', $recep);
                            }
                        } else {
                            $this->emit('item-error', 'Este DTE necesita tener Sello de Recepcion para poder ser Anulado');
                        }
                    } else {
                        if ($dte->sello != null) {
                            $invalida = InvalidacionDte::create([
                                'tipo' => $this->tipo,
                                'ambiente' => $ambiente->id,
                                'numero' => $this->numero,
                                'codigoGeneracion' => strtoupper(Str::uuid()->toString()),
                                'fecAnula' => date('Y-m-d'),
                                'horAnula' => date('H:i:s'),
                                'emisor' => $dte->emisor,
                                'dte' => $this->dteid,
                                'codigoGeneracionR' => $this->dterem,
                                'tipoAnulacion' => $this->motivo,
                                'motivoAnulacion' => $this->desmotivo,
                                'responsable' => Auth::user()->id,
                                'solicita' => Auth::user()->id,
                                'caja' => $caja->id,
                                'sucursal' => $sucursal->id,
                                'empresa' => $empresa->id,
                                'estado' => 'Generado',
                                'selloRecibido' => null,
                                'fhProcesamiento' => null,
                                'descripcionMsg' => null,
                                'json' => null,
                                'jsonRespuesta' => null,
                                'docFirmado' => null,
                            ]);

                            $json = $this->generarJsonInva($invalida->id);
                            $firmador = $this->Firmador2($json);

                            $invalida->json = $json;
                            $invalida->docFirmado = $firmador;
                            $invalida->save();

                            $recep = $this->RecepcionInvalidacion($invalida->id);

                            if ($recep == 'RECHAZADO') {
                                $this->ResetUI();
                                $this->emit('item-error', $recep);
                            } else {
                                $dte = dte::find($invalida->dte);
                                $dte->estado = 'INVALIDADO';
                                $dte->save();
                                $this->ResetUI();
                                $this->emit('item-added', $recep);
                            }
                        } else {
                            $this->emit('item-error', 'Este DTE necesita tener Sello de Recepcion para poder ser Anulado');
                        }
                    }
                } else {
                    $this->emit('item-error', 'El registro ha superado el período de vigencia.');
                }
            }
        }
    }

    public function ResetUI()
    {
        $this->selected_id = '';
        $this->numero = '';
        $this->tipo = '';
        $this->motivo = '';
        $this->desmotivo = '';
        $this->dteinva = '';
        $this->dteid = '';
        $this->dterem = '';
        $this->estado = '';
        $this->detalle = '';
    }

    public function Edit($id)
    {
        $inva = InvalidacionDte::find($id);
        $dte = dte::find($inva->dte);

        $this->selected_id = $id;
        $this->numero = $inva->numero;
        $this->tipo = $inva->tipo;
        $this->motivo = $inva->tipoAnulacion;
        $this->desmotivo = $inva->motivoAnulacion;
        $this->dteinva = $dte->codigoGeneracion;
        $this->dteid = $inva->dte;
        $this->dterem = $inva->codigoGeneracionR;
        $this->estado = $inva->estado;
        $this->detalle = $inva->descripcionMsg;
        $this->emit('show-modal', 'show modal');
    }

    public function Update()
    {
        $inva = InvalidacionDte::find($this->selected_id);

        $inva->tipo = $this->tipo;
        $inva->tipoAnulacion = $this->motivo;
        $inva->dte = $this->dteid;
        $inva->codigoGeneracionR = $this->dterem;
        $inva->json = $this->generarJsonInva($this->selected_id);
        $inva->save();

        $this->resetUI();
        $this->emit('item-updated', 'Datos Actualizados');
    }
    public function ProcesarInva($id)
    {
        $user = Auth::user();
        $invalida = InvalidacionDte::find($id);

        $json = $this->generarJsonInva($id);

        $firmador = $this->Firmador2($json);
        $invalida->json = $json;
        $invalida->docFirmado = $firmador;
        $invalida->save();

        $recep = $this->RecepcionInvalidacion($invalida->id);

        if ($recep == 'RECHAZADO') {
            $this->ResetUI();
            $this->emit('item-error', $recep);
        } else {
            $dte = dte::find($invalida->dte);
            $dte->estado = 'INVALIDADO';
            $dte->save();
            $this->ResetUI();
            $this->emit('item-added', $recep);
        }
    }
}
