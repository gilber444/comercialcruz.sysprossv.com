<?php

namespace App\Http\Livewire;

use App\Models\EventoRetorno;
use App\Services\GeneradorEventoRetorno;
use App\Traits\RecepcionEventoRetorno;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Listado y transmision de los Eventos de Retorno.
 *
 * Mismo ciclo que la invalidacion: generar el JSON, firmarlo, transmitirlo y
 * guardar el sello. Al reintentar se genera un codigoGeneracion nuevo, porque
 * Hacienda no acepta reutilizar el identificador de un evento rechazado.
 */
class EventoRetornoController extends Component
{
    use WithPagination;
    use RecepcionEventoRetorno;

    public $pageTitle, $componentName, $search = '';
    private $pagination = 10;

    public function mount()
    {
        $this->pageTitle = 'Eventos de Retorno';
        $this->componentName = 'Documentos DTE';
    }

    public function paginationView()
    {
        return 'vendor.livewire.bootstrap';
    }

    public function render()
    {
        try {
            $data = $this->listado();
        } catch (\Throwable $e) {
            Log::error('No se pudo listar los eventos de retorno', [
                'mensaje' => $e->getMessage(),
                'archivo' => $e->getFile() . ':' . $e->getLine(),
            ]);

            $this->emit('item-error', 'No se pudo cargar el listado: ' . $e->getMessage());

            // Paginador vacio en memoria. No se puede consultar la tabla aqui:
            // si el fallo fue justamente que no existe, el respaldo reventaria
            // tambien y la excepcion escaparia a la pantalla.
            $data = new LengthAwarePaginator([], 0, $this->pagination, 1, [
                'path' => Paginator::resolveCurrentPath(),
            ]);
        }

        return view('livewire.evento_retorno.listado', ['eventos' => $data])
            ->extends('layouts.theme.app')
            ->section('content');
    }

    private function listado()
    {
        $usuario = Auth::user();

        return EventoRetorno::with(['Rventa', 'Rcliente', 'Rdetalles'])
            ->where('empresa', $usuario->empresa)
            ->when(trim($this->search) !== '', function ($q) {
                $s = trim($this->search);
                $q->where(function ($w) use ($s) {
                    $w->where('codigoGeneracion', 'like', '%' . $s . '%')
                        ->orWhere('selloRecibido', 'like', '%' . $s . '%')
                        ->orWhereHas('Rventa', fn ($v) => $v->where('correlativo', 'like', '%' . $s . '%'));
                });
            })
            ->orderByDesc('fecEmi')
            ->orderByDesc('id')
            ->paginate($this->pagination);
    }

    /**
     * Genera, firma y transmite. Sirve tanto para el primer envio como para
     * reintentar uno rechazado.
     */
    public function Procesar($id)
    {
        try {
            $evento = EventoRetorno::whereKey($id)
                ->where('empresa', Auth::user()->empresa)
                ->first();
        } catch (\Throwable $e) {
            Log::error('No se pudo cargar el evento de retorno', [
                'mensaje' => $e->getMessage(),
                'archivo' => $e->getFile() . ':' . $e->getLine(),
            ]);
            $this->emit('item-error', 'No se pudo cargar el evento: ' . $e->getMessage());
            return;
        }

        // Sin firstOrFail: lanzaria ModelNotFoundException y el usuario veria la
        // pantalla de error en vez del aviso.
        if (!$evento) {
            $this->emit('item-error', 'El evento no existe o no pertenece a su empresa.');
            return;
        }

        if ($evento->selloRecibido) {
            $this->emit('item-error', 'Este evento ya tiene sello de recepcion.');
            return;
        }

        try {
            // Identificador y fecha nuevos: Hacienda rechaza reutilizar el
            // codigoGeneracion de un evento que ya fue rechazado.
            // La fecha y hora del evento tambien se renuevan: es un documento
            // nuevo para Hacienda y el plazo se evalua contra esta fecha.
            $ahora = Carbon::now();
            $evento->codigoGeneracion = strtoupper(Str::uuid()->toString());
            $evento->fecEmi = $ahora->format('Y-m-d');
            $evento->horEmi = $ahora->format('H:i:s');
            $evento->estado = 'Generado';
            $evento->selloRecibido = null;
            $evento->fhProcesamiento = null;
            $evento->descripcionMsg = null;
            $evento->jsonRespuesta = null;
            $evento->save();

            // El codigoGeneracion del evento cambio; los items referencian el del
            // DTE de origen, que no se toca.
            $json = app(GeneradorEventoRetorno::class)->generar($evento->id);

            // Firmador2 de este proyecto no lanza excepcion: ante un fallo
            // devuelve un texto o una respuesta HTTP. Un JWS valido tiene tres
            // partes separadas por punto; cualquier otra cosa no se transmite.
            $firmado = $this->Firmador2($json);

            if (!is_string($firmado) || substr_count($firmado, '.') !== 2 || str_contains($firmado, ' ')) {
                $detalle = is_string($firmado)
                    ? $firmado
                    : (method_exists($firmado, 'getContent') ? (string) $firmado->getContent() : 'respuesta no valida');

                throw new \RuntimeException('No se pudo firmar el evento: ' . mb_substr($detalle, 0, 200));
            }

            $evento->docFirmado = $firmado;
            $evento->estado = 'Firmado';
            $evento->save();

            $estado = $this->RecepcionEventoRetorno($evento->id);

            $evento->refresh();

            if ($estado === 'PROCESADO') {
                $this->emit('item-added', 'Evento transmitido. Sello: ' . $evento->selloRecibido);
            } else {
                $this->emit('item-error', $estado . ': ' . ($evento->descripcionMsg ?: 'sin detalle'));
            }
        } catch (\Throwable $e) {
            Log::error('No se pudo transmitir el evento de retorno', [
                'evento' => $evento->id,
                'mensaje' => $e->getMessage(),
                'archivo' => $e->getFile() . ':' . $e->getLine(),
            ]);

            try {
                $evento->estado = 'Creado';
                $evento->descripcionMsg = mb_substr($e->getMessage(), 0, 255);
                $evento->save();
            } catch (\Throwable $ignorado) {
                // El aviso al usuario importa mas que dejar el estado escrito.
            }

            $this->emit('item-error', $e->getMessage());
        }
    }

    /** Representacion grafica: la normativa obliga a entregarsela al cliente. */
    public function pdf($id)
    {
        try {
            $evento = EventoRetorno::with([
                'Rdetalles', 'Rventa', 'Rcliente', 'Rdte', 'Rsucursal', 'Rempresa', 'Rresponsable',
            ])
                ->whereKey($id)
                ->where('empresa', Auth::user()->empresa)
                ->firstOrFail();

            $pdf = Pdf::loadView('pdf.eventoRetorno', ['evento' => $evento])->setPaper('letter');

            return response($pdf->output(), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="RETORNO-' . $evento->codigoGeneracion . '.pdf"',
            ]);
        } catch (\Throwable $e) {
            return $this->respuestaError($e, 'No se pudo generar el PDF del evento');
        }
    }

    /**
     * Estas acciones abren en una pestana nueva, donde item-error no existe: se
     * responde con un texto legible en vez de la pantalla de excepcion.
     */
    private function respuestaError(\Throwable $e, string $contexto)
    {
        Log::error($contexto, [
            'componente' => static::class,
            'mensaje' => $e->getMessage(),
            'archivo' => $e->getFile() . ':' . $e->getLine(),
        ]);

        $codigo = $e instanceof ModelNotFoundException ? 404 : 500;
        $texto = $e instanceof ModelNotFoundException
            ? 'El evento no existe o no pertenece a su empresa.'
            : $contexto . ': ' . $e->getMessage();

        return response($texto, $codigo, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    public function showJson($id)
    {
        return $this->respuestaJson((int) $id, 'inline');
    }

    public function downloadJson($id)
    {
        return $this->respuestaJson((int) $id, 'attachment');
    }

    private function respuestaJson(int $id, string $disposicion)
    {
        try {
            $evento = EventoRetorno::whereKey($id)
                ->where('empresa', Auth::user()->empresa)
                ->firstOrFail();

            $json = $evento->json ?: app(GeneradorEventoRetorno::class)->generar($evento->id);

            $bonito = json_encode(
                json_decode($json, true),
                JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            );

            return response($bonito, 200, [
                'Content-Type' => 'application/json',
                'Content-Disposition' => $disposicion . '; filename="RETORNO-' . $evento->codigoGeneracion . '.json"',
            ]);
        } catch (\Throwable $e) {
            return $this->respuestaError($e, 'No se pudo obtener el JSON del evento');
        }
    }
}
