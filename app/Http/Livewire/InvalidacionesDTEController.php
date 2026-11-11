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
use Illuminate\Support\Facades\DB;
use App\Traits\RecepcionInvalidacion;
use App\Traits\enviarCorreoDTE;
use App\Services\InvalidacionEntregaService;
use App\Services\PlazoInvalidacionDteService;
use App\Traits\CatalogoRequerido;

class InvalidacionesDTEController extends Component
{
    use CatalogoRequerido;
    use WithPagination;
    use GenerarJsonInva;
    use RecepcionInvalidacion;
    use Firmador;
    //use FirmadorDTE;
    use Firmador2;
    use enviarCorreoDTE;

    public $search, $selected_id, $pageTitle, $componentName, $numero, $tipo, $motivos, $motivo, $desmotivo, $dteinva, $dteid, $dterem, $estado, $detalle;
    private $pagination = 7;

    public function mount()
    {
        $user = Auth::user();
        $this->pageTitle = 'Listado';
        $this->componentName = 'DTE Invalidados';
        $this->motivos = TipoInvalidacion::all();

        $ultimoNumero = InvalidacionDte::where('empresa', $user->empresa)->max('numero');

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
        // Aislamiento por empresa tambien al buscar (antes el filtro de
        // empresa se aplicaba despues de paginar y no tenia efecto).
        $query = InvalidacionDte::where('empresa', $user->empresa);

        if (strlen((string) $this->search) > 0) {
            $search = trim($this->search);

            if (preg_match('/^[0-9A-Fa-f-]{36}$/', $search)) {
                $query->where('codigoGeneracion', $search);
            } else {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('codigoGeneracion', 'like', '%' . $search . '%')
                        ->orWhere('fecAnula', 'like', $search . '%')
                        ->orWhere('tipo', 'like', $search . '%');
                });
            }
        }

        $data = $query->orderBy('numero', 'desc')->paginate($this->pagination);

        return view('livewire.dtes.invalidaciones-d-t-e', ['invalidaciones' => $data])
            ->extends('layouts.theme.app')
            ->section('content');
    }

    public function cargarDatos()
    {
        $dte = dte::select('id', 'codigoGeneracion')
            ->where('empresa', Auth::user()->empresa)
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

        try {
            $user = Auth::user();

            $empresa = $this->catalogoRequerido(
                Empresas::find($user->empresa),
                'la empresa id ' . $user->empresa . ' (catalogo empresas)'
            );

            // dtes.tipoDte es columna y relacion: se carga la relacion para
            // que PlazoInvalidacionDteService no repita la consulta.
            $dte = dte::with('tipoDte:id,codigo')
                ->whereKey($this->dteid)
                ->where('empresa', $user->empresa)
                ->first();

            if (!$dte) {
                $this->emit('item-error', 'DTE no Registrado');
                return;
            }

            $this->crearYProcesarInvalidacion($dte, $empresa);
        } catch (\Throwable $e) {
            $this->emit('item-error', 'No se pudo invalidar el DTE: ' . $e->getMessage());
        }
    }

    /**
     * Flujo normativo 2.0: valida sello, plazo, fecha del evento y documento
     * de reemplazo; crea el evento, lo firma, lo transmite y, si MH lo acepta,
     * marca el DTE como INVALIDADO y envia el correo del evento.
     */
    private function crearYProcesarInvalidacion(dte $dte, Empresas $empresa): void
    {
        $tipo = trim((string) $this->tipo);

        if (empty($dte->sello)) {
            $this->emit('item-error', 'Este DTE necesita tener Sello de Recepcion para poder ser Anulado');
            return;
        }

        $plazos = app(PlazoInvalidacionDteService::class);

        try {
            if (!$plazos->vigente($dte)) {
                $this->emit('item-error', $plazos->mensajeVencimiento($dte));
                return;
            }

            // CCF/NC: la fecha de generacion del DTE; FE/FSE: hoy, dentro de
            // los tres meses permitidos.
            $fechaEvento = $plazos->fechaEvento($dte)->toDateString();
            $codigoReemplazo = $this->codigoReemplazoNormativo($dte, $empresa, $plazos);
        } catch (\DomainException $e) {
            $this->emit('item-error', $e->getMessage());
            return;
        }

        $ambiente = $this->catalogoRequerido(
            AmbienteDestino::find($empresa->ambiente),
            'el ambiente de destino id ' . $empresa->ambiente . ' (catalogo ambiente_destinos)'
        );
        $sucursal = $this->catalogoRequerido(
            Sucursales::find($dte->sucursal),
            'la sucursal id ' . $dte->sucursal . ' del DTE (catalogo sucursales)'
        );
        $caja = $this->catalogoRequerido(
            Parametros::find($dte->caja),
            'la caja id ' . $dte->caja . ' del DTE (catalogo parametros)'
        );

        $invalidacion = DB::transaction(function () use ($dte, $empresa, $ambiente, $sucursal, $caja, $tipo, $fechaEvento, $codigoReemplazo) {
            $numero = ((int) InvalidacionDte::where('empresa', $empresa->id)
                ->lockForUpdate()
                ->max('numero')) + 1;

            return InvalidacionDte::create([
                'tipo' => $tipo,
                'ambiente' => $ambiente->id,
                'numero' => $numero,
                'codigoGeneracion' => strtoupper(Str::uuid()->toString()),
                'fecAnula' => $fechaEvento,
                'horAnula' => now()->format('H:i:s'),
                'emisor' => $dte->emisor,
                'dte' => $dte->id,
                'codigoGeneracionR' => $codigoReemplazo,
                'tipoAnulacion' => $this->motivo,
                'motivoAnulacion' => $this->desmotivo,
                'responsable' => Auth::id(),
                'solicita' => Auth::id(),
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
        });

        $json = $this->generarJsonInva($invalidacion->id);
        $invalidacion->json = $json;
        $invalidacion->docFirmado = $this->Firmador2($json);
        $invalidacion->save();

        $estadoMh = $this->RecepcionInvalidacion($invalidacion->id);

        if (!$this->invalidacionAceptada($invalidacion)) {
            $this->ResetUI();
            $this->emit('item-error', $this->mensajeInvalidacionNoAceptada($invalidacion, $estadoMh));
            return;
        }

        $this->finalizarInvalidacionAceptada($invalidacion, (string) $estadoMh);
    }

    /**
     * Solo un evento PROCESADO y con sello cuenta como aceptado. Antes todo lo
     * que no fuera literalmente "RECHAZADO" (una respuesta sin JSON, null o
     * ERROR) marcaba el DTE como INVALIDADO y mandaba el correo.
     */
    private function invalidacionAceptada(InvalidacionDte $invalidacion): bool
    {
        $actual = $invalidacion->fresh();

        return $actual
            && strtoupper((string) $actual->estado) === 'PROCESADO'
            && trim((string) $actual->selloRecibido) !== '';
    }

    private function mensajeInvalidacionNoAceptada(InvalidacionDte $invalidacion, $estadoMh): string
    {
        $actual = $invalidacion->fresh();
        $estado = trim((string) ($estadoMh ?: optional($actual)->estado)) ?: 'SIN RESPUESTA';
        $detalle = trim((string) optional($actual)->descripcionMsg);

        return 'Hacienda no acepto la invalidacion (' . $estado . ')'
            . ($detalle !== '' ? ': ' . $detalle : '.');
    }

    /**
     * codigoGeneracionR segun CAT-024 y el tipo de DTE:
     * - motivo 2 (rescindir): siempre null;
     * - motivos 1 y 3: FE, CCF y FSE exigen un reemplazo del mismo tipo, de la
     *   misma empresa, ya procesado y sellado;
     * - Nota de Credito: null (la sustituta se emite despues de aceptar la
     *   invalidacion).
     */
    private function codigoReemplazoNormativo(dte $dte, Empresas $empresa, PlazoInvalidacionDteService $plazos): ?string
    {
        $motivo = TipoInvalidacion::find($this->motivo);

        if (!$motivo) {
            throw new \DomainException('Seleccione un motivo de invalidacion valido (catalogo tipo_invalidacions).');
        }

        $codigoMotivo = (int) $motivo->codigo;
        $tipoDte = $plazos->codigoTipo($dte);

        // Rescindir la operacion nunca lleva documento de reemplazo.
        if ($codigoMotivo === 2) {
            return null;
        }

        if (!in_array($codigoMotivo, [1, 3], true)) {
            throw new \DomainException('El motivo seleccionado no corresponde al CAT-024 vigente.');
        }

        // La NC se invalida primero y su sustituta se emite despues.
        if ($tipoDte === '05') {
            return null;
        }

        if (!in_array($tipoDte, ['01', '03', '14'], true)) {
            throw new \DomainException("El tipo de DTE {$tipoDte} no está habilitado para este flujo de invalidación.");
        }

        $codigo = strtoupper(trim((string) $this->dterem));

        if ($codigo === '') {
            throw new \DomainException(
                'Para el motivo 1 o 3 debe generar y procesar primero el DTE que reemplazará al documento incorrecto.'
            );
        }

        $reemplazo = dte::query()
            ->where('empresa', $empresa->id)
            ->whereRaw('UPPER(codigoGeneracion) = ?', [$codigo])
            ->where('tipoDte', $dte->getAttribute('tipoDte'))
            ->whereNotNull('sello')
            ->whereIn('estado', ['Procesado', 'PROCESADO'])
            ->first();

        if (!$reemplazo) {
            throw new \DomainException(
                'El DTE de reemplazo debe ser del mismo tipo, pertenecer a la empresa y estar procesado con Sello de Recepción.'
            );
        }

        if ((int) $reemplazo->id === (int) $dte->id) {
            throw new \DomainException('El DTE de reemplazo no puede ser el mismo documento que se invalidará.');
        }

        return strtoupper((string) $reemplazo->codigoGeneracion);
    }

    private function finalizarInvalidacionAceptada(InvalidacionDte $invalidacion, string $estadoMh): void
    {
        $dte = dte::find($invalidacion->dte);

        if ($dte) {
            $dte->estado = 'INVALIDADO';
            $dte->save();
        }

        // El correo es complementario: si falla, el evento ya quedo aceptado.
        try {
            $correo = $this->enviarCorreoEventoInvalidacion($invalidacion->id);
        } catch (\Throwable $e) {
            $correo = 'Error al enviar correo de invalidación: ' . $e->getMessage();
        }

        $this->ResetUI();
        $this->emit('item-added', $estadoMh . '. ' . $correo);
    }

    /**
     * Reenvio manual del correo del evento desde el listado de invalidaciones.
     */
    public function reenviarCorreoInvalidacion($id): void
    {
        try {
            if (!Auth::user()->can('DTE_ReenviarCorreo')) {
                $this->emit('item-error', 'No tiene permiso para reenviar correos DTE.');
                return;
            }

            $invalidacion = InvalidacionDte::whereKey((int) $id)
                ->where('empresa', Auth::user()->empresa)
                ->where('estado', 'PROCESADO')
                ->first();

            if (!$invalidacion) {
                $this->emit('item-error', 'La invalidacion no existe o aun no ha sido procesada.');
                return;
            }

            $resultado = $this->enviarCorreoEventoInvalidacion($invalidacion->id);
            $evento = str_starts_with($resultado, 'Error') ? 'item-error' : 'item-added';

            $this->emit($evento, $resultado);
        } catch (\Throwable $e) {
            $this->emit('item-error', 'Error al reenviar el correo: ' . $e->getMessage());
        }
    }

    /**
     * Representacion grafica del evento aceptado, para el usuario del sistema.
     */
    public function pdf($id)
    {
        return $this->respuestaPdfInvalidacion($this->invalidacionProcesadaDeEmpresa((int) $id));
    }

    /**
     * Consulta publica del evento: es el destino del QR que lleva el PDF, y el
     * receptor la abre sin sesion. Solo expone eventos ya procesados.
     */
    public function pdfPublic($codigo)
    {
        $invalidacion = InvalidacionDte::where('codigoGeneracion', $codigo)
            ->where('estado', 'PROCESADO')
            ->firstOrFail();

        return $this->respuestaPdfInvalidacion($invalidacion);
    }

    public function showJson($id)
    {
        return $this->respuestaJsonInvalidacion((int) $id, 'inline');
    }

    public function downloadJson($id)
    {
        return $this->respuestaJsonInvalidacion((int) $id, 'attachment');
    }

    private function respuestaPdfInvalidacion(InvalidacionDte $invalidacion)
    {
        $entrega = app(InvalidacionEntregaService::class)->generar($invalidacion->id);
        $nombre = 'INVALIDACION-' . $invalidacion->codigoGeneracion . '.pdf';

        return response($entrega['pdf'], 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $nombre . '"',
        ]);
    }

    private function respuestaJsonInvalidacion(int $id, string $disposicion)
    {
        $invalidacion = $this->invalidacionProcesadaDeEmpresa($id);
        $jsonCompacto = app(InvalidacionEntregaService::class)
            ->construirArchivoJson($invalidacion);
        $contenido = json_decode($jsonCompacto, true, 512, JSON_THROW_ON_ERROR);
        $jsonLegible = json_encode(
            $contenido,
            JSON_PRETTY_PRINT
                | JSON_UNESCAPED_UNICODE
                | JSON_UNESCAPED_SLASHES
                | JSON_PRESERVE_ZERO_FRACTION
                | JSON_THROW_ON_ERROR
        );
        $nombre = 'INVALIDACION-' . $invalidacion->codigoGeneracion . '.json';

        return response($jsonLegible, 200, [
            'Content-Type' => 'application/json; charset=utf-8',
            'Content-Disposition' => $disposicion . '; filename="' . $nombre . '"',
        ]);
    }

    /** Aislamiento por empresa: nunca se sirve el evento de otro contribuyente. */
    private function invalidacionProcesadaDeEmpresa(int $id): InvalidacionDte
    {
        return InvalidacionDte::whereKey($id)
            ->where('empresa', Auth::user()->empresa)
            ->where('estado', 'PROCESADO')
            ->firstOrFail();
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
        $inva = InvalidacionDte::whereKey($id)
            ->where('empresa', Auth::user()->empresa)
            ->first();

        if (!$inva) {
            $this->emit('item-error', 'La invalidacion no existe.');
            return;
        }

        $dte = dte::find($inva->dte);

        $this->selected_id = $id;
        $this->numero = $inva->numero;
        $this->tipo = $inva->tipo;
        $this->motivo = $inva->tipoAnulacion;
        $this->desmotivo = $inva->motivoAnulacion;
        $this->dteinva = optional($dte)->codigoGeneracion;
        $this->dteid = $inva->dte;
        $this->dterem = $inva->codigoGeneracionR;
        $this->estado = $inva->estado;
        $this->detalle = $inva->descripcionMsg;
        $this->emit('show-modal', 'show modal');
    }

    public function Update()
    {
        try {
            $inva = InvalidacionDte::whereKey($this->selected_id)
                ->where('empresa', Auth::user()->empresa)
                ->firstOrFail();

            // Un evento ya aceptado por Hacienda no se edita.
            if (strtoupper((string) $inva->estado) === 'PROCESADO') {
                $this->emit('item-error', 'Esta invalidacion ya fue procesada por Hacienda y no se puede modificar.');
                return;
            }

            // dteid llega del navegador: el DTE tiene que ser de la misma empresa.
            $dteEmpresa = dte::whereKey($this->dteid)
                ->where('empresa', Auth::user()->empresa)
                ->exists();
            if (!$dteEmpresa) {
                $this->emit('item-error', 'DTE no Registrado');
                return;
            }

            $inva->tipo = $this->tipo;
            $inva->tipoAnulacion = $this->motivo;
            $inva->dte = $this->dteid;
            $inva->codigoGeneracionR = $this->dterem;
            $inva->save();
            $inva->json = $this->generarJsonInva($this->selected_id);
            $inva->save();

            $this->resetUI();
            $this->emit('item-updated', 'Datos Actualizados');
        } catch (\Throwable $e) {
            $this->emit('item-error', 'No se pudo actualizar la invalidacion: ' . $e->getMessage());
        }
    }

    public function ProcesarInva($id)
    {
        try {
            $invalida = InvalidacionDte::whereKey($id)
                ->where('empresa', Auth::user()->empresa)
                ->firstOrFail();

            if ($invalida->estado === 'PROCESADO') {
                $this->emit('item-error', 'Esta invalidacion ya fue procesada por Hacienda.');
                return;
            }

            // El DTE tambien se filtra por empresa: nunca se invalida el de otra.
            $dte = dte::with('tipoDte:id,codigo')
                ->whereKey($invalida->dte)
                ->where('empresa', Auth::user()->empresa)
                ->firstOrFail();

            if (trim((string) $dte->sello) === '') {
                $this->emit('item-error', 'Este DTE necesita tener Sello de Recepcion para poder ser Anulado');
                return;
            }

            $plazos = app(PlazoInvalidacionDteService::class);
            $ahora = Carbon::now();

            if (!$plazos->vigente($dte, $ahora)) {
                $this->emit('item-error', $plazos->mensajeVencimiento($dte));
                return;
            }

            // Cada reenvio lleva un UUID nuevo (no se reutiliza el de un evento
            // rechazado), la fecha valida del evento y la hora actual.
            $invalida->codigoGeneracion = strtoupper(Str::uuid()->toString());
            $invalida->fecAnula = $plazos->fechaEvento($dte, $ahora)->toDateString();
            $invalida->horAnula = $ahora->format('H:i:s');
            $invalida->estado = 'Generado';
            $invalida->selloRecibido = null;
            $invalida->fhProcesamiento = null;
            $invalida->descripcionMsg = null;
            $invalida->jsonRespuesta = null;
            $invalida->save();

            $json = $this->generarJsonInva($invalida->id);

            $firmador = $this->Firmador2($json);
            $invalida->json = $json;
            $invalida->docFirmado = $firmador;
            $invalida->save();

            $recep = $this->RecepcionInvalidacion($invalida->id);

            if (!$this->invalidacionAceptada($invalida)) {
                $this->ResetUI();
                $this->emit('item-error', $this->mensajeInvalidacionNoAceptada($invalida, $recep));
            } else {
                $this->finalizarInvalidacionAceptada($invalida, (string) $recep);
            }
        } catch (\DomainException $e) {
            $this->emit('item-error', $e->getMessage());
        } catch (\Throwable $e) {
            $this->emit('item-error', 'No se pudo procesar la invalidacion: ' . $e->getMessage());
        }
    }
}
