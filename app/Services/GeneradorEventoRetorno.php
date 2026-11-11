<?php

namespace App\Services;

use App\Helpers\Convertidor;
use App\Models\Clientes;
use App\Models\dte;
use App\Models\Empresas;
use App\Models\EsquemaDte;
use App\Models\EventoRetorno;
use App\Models\Parametros;
use App\Models\TipoItem;
use App\Models\Ventas;
use App\Support\JsonComprimido;
use App\Support\NormalizadorCorreoDte;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Construye el JSON del Evento de Retorno (CAT-002 codigo 18) segun el schema
 * fe-eret-v1 del Ministerio de Hacienda.
 *
 * Un evento no es un DTE: no lleva numeroControl ni consume correlativos. Solo
 * se identifica con un codigoGeneracion (UUID).
 *
 * Las reglas de la Normativa de Cumplimiento V2.0 que se validan aqui:
 *  - Solo aplica a Factura (01), Factura de Exportacion (11) y Sujeto Excluido (14).
 *    Nunca a Credito Fiscal: para eso esta la Nota de Credito.
 *  - Solo a DTE que ya tengan sello de recepcion, nunca a transitorios.
 *  - Plazo de 3 meses desde la fecha de generacion del DTE (24 meses si la
 *    actividad economica del emisor es una de las excepciones).
 *  - La fecha del evento no puede ser anterior a la del DTE.
 *  - El monto retornado no puede exceder el del DTE, contando lo ya retornado
 *    en eventos anteriores sobre la misma factura.
 */
class GeneradorEventoRetorno
{
    /** Version de respaldo si no hay esquema cargado en esquema_dtes. */
    private const VERSION_RESPALDO = 1;

    private const TIPO_EVENTO = '18';

    /**
     * Arma el JSON, lo valida y lo deja persistido en el evento.
     */
    public function generar(int $eventoId): string
    {
        $evento = $this->cargar($eventoId);

        $this->validar($evento);

        $estructura = $this->estructura($evento);

        $json = json_encode(
            $estructura,
            JSON_UNESCAPED_UNICODE
                | JSON_UNESCAPED_SLASHES
                | JSON_PRESERVE_ZERO_FRACTION
                | JSON_THROW_ON_ERROR
        );

        $this->validarContraEsquema($json, $evento->codigoGeneracion);

        $evento->json = $json;
        // La representacion grafica imprime "Son: ..." desde la cabecera; sin
        // esto el PDF salia con el monto en letras vacio.
        $evento->totalLetras = mb_substr((string) $estructura['resumen']['totalLetras'], 0, 200);
        $evento->save();

        return $json;
    }

    /**
     * Contrasta el evento con su JSON Schema oficial. Modo en config/dte.php:
     * off, aviso o bloqueo.
     */
    private function validarContraEsquema(string $json, ?string $codigoGeneracion = null): void
    {
        $modo = (string) config('dte.validar_esquema', 'aviso');

        if ($modo === 'off') {
            return;
        }

        $resultado = app(ValidadorEsquemaDte::class)->validar(self::TIPO_EVENTO, $json);

        if (!$resultado['evaluado'] || $resultado['valido']) {
            return;
        }

        $detalle = collect($resultado['errores'])
            ->map(fn ($e) => $e['campo'] . ': ' . $e['mensaje'])
            ->take(10)
            ->implode(' | ');

        Log::warning('Evento de retorno fuera del schema oficial', [
            'version' => $resultado['version'],
            'codigoGeneracion' => $codigoGeneracion,
            'errores' => $detalle,
        ]);

        if ($modo === 'bloqueo') {
            throw new RuntimeException(
                'El evento no cumple el schema v' . $resultado['version'] . ': ' . $detalle
            );
        }
    }

    /**
     * Version vigente del esquema del evento. Igual que el generador de DTE:
     * manda la tabla esquema_dtes y el codigo queda de respaldo.
     */
    public function version(): int
    {
        try {
            $version = EsquemaDte::versionVigente(self::TIPO_EVENTO);

            if ($version !== null) {
                return (int) $version;
            }
        } catch (\Throwable $e) {
            // Nunca se bloquea la emision por no poder leer el esquema.
        }

        return self::VERSION_RESPALDO;
    }

    /**
     * Errores que impiden aplicar un retorno a una venta. Devuelve lista vacia
     * si se puede. La usa la pantalla antes de dejar armar el evento.
     */
    public function motivosNoAplicable(Ventas $venta, ?string $fechaEvento = null): array
    {
        $errores = [];

        $dteVenta = dte::with('RtipoDte:id,codigo')->where('venta', $venta->id)->latest('id')->first();

        if (!$dteVenta) {
            return ['La venta no tiene un DTE asociado.'];
        }

        $tipo = str_pad((string) optional($dteVenta->RtipoDte)->codigo, 2, '0', STR_PAD_LEFT);

        if (!in_array($tipo, EventoRetorno::TIPOS_APLICABLES, true)) {
            $errores[] = 'El evento de retorno solo aplica a Factura, Factura de Exportacion y '
                . 'Sujeto Excluido. Para Credito Fiscal debe emitir una Nota de Credito.';
        }

        if (trim((string) $dteVenta->sello) === '') {
            $errores[] = 'El DTE no tiene sello de recepcion. No se puede aplicar un evento a un documento transitorio.';
        }

        // En este proyecto la invalidacion deja dtes.estado = 'INVALIDADO'.
        if (strtoupper(trim((string) $dteVenta->estado)) === 'INVALIDADO') {
            $errores[] = 'El DTE fue invalidado. No se puede aplicar un evento de retorno a un documento invalidado.';
        }

        // Ventas guarda el id de la empresa, no una relacion; el plazo depende de
        // la actividad economica del emisor.
        $empresa = Empresas::with('Ractividad:id,codigo')->find($venta->empresa);
        $meses = EventoRetorno::plazoMeses($empresa);

        $emision = Carbon::parse($dteVenta->fecEmi)->startOfDay();
        $fecha = Carbon::parse($fechaEvento ?: now()->format('Y-m-d'))->startOfDay();

        if ($fecha->lt($emision)) {
            $errores[] = 'La fecha del evento no puede ser anterior a la fecha de generacion del DTE ('
                . $emision->format('d/m/Y') . ').';
        }

        if ($emision->copy()->addMonths($meses)->lt($fecha)) {
            $errores[] = 'Vencio el plazo para aplicar el evento: son ' . $meses . ' meses desde el '
                . $emision->format('d/m/Y') . '.';
        }

        return $errores;
    }

    /**
     * Monto ya retornado sobre una venta en eventos anteriores que no fueron
     * rechazados. Sirve para que la suma de retornos no supere el DTE.
     */
    public function retornadoPrevio(int $ventaId, ?int $excluirEvento = null): float
    {
        return (float) EventoRetorno::where('venta', $ventaId)
            ->whereNotIn('estado', ['RECHAZADO', 'Anulado'])
            ->when($excluirEvento, fn ($q) => $q->where('id', '!=', $excluirEvento))
            ->sum('montoTotalOperacion');
    }

    // ------------------------------------------------------------------ armado

    private function estructura(EventoRetorno $evento): array
    {
        $dteVenta = $evento->Rdte;
        $items = [];
        $totalGravada = 0.0;
        $totalIva = 0.0;

        foreach ($evento->Rdetalles as $indice => $detalle) {
            $gravada = $this->dinero($detalle->ventaGravada);
            $iva = $this->dinero($detalle->ivaItem);

            $totalGravada += $gravada;
            $totalIva += $iva;

            $items[] = [
                'numItem' => $indice + 1,
                'tipoItem' => (int) ($detalle->tipoItem ?: $this->tipoItem()),
                'codigoGeneracion' => strtoupper((string) $detalle->codigoGeneracion),
                'cantidad' => $this->numero($detalle->cantidad, 8),
                'precioUni' => $this->numero($detalle->precioUni, 8),
                'descripcion' => trim((string) $detalle->descripcion),
                'codigo' => $this->texto($detalle->codigo, 25),
                'uniMedida' => (int) ($detalle->uniMedida ?: 59),
                'montoDescu' => $this->numero($detalle->montoDescu, 8),
                'codTributo' => null,
                'ventaNoSuj' => $this->dinero($detalle->ventaNoSuj),
                'ventaExenta' => $this->dinero($detalle->ventaExenta),
                'ventaGravada' => $gravada,
                'compra' => 0.0,
                'tributos' => null,
                'psv' => $this->dinero($detalle->psv),
                'ivaItem' => $iva,
                'noGravado' => $this->dinero($detalle->noGravado),
                'seguro' => 0.0,
                'flete' => 0.0,
                'ivaRete' => 0.0,
                'reteRenta' => 0.0,
            ];
        }

        $totalGravada = $this->dinero($totalGravada);
        $totalIva = $this->dinero($totalIva);

        return [
            'identificacion' => [
                'version' => $this->version(),
                'ambiente' => str_pad(trim((string) $evento->Rambiente->codigo), 2, '0', STR_PAD_LEFT),
                'tipoModelo' => (int) $evento->tipoModelo,
                'tipoOperacion' => (int) $evento->tipoOperacion,
                'tipoEvento' => self::TIPO_EVENTO,
                'tipoContingencia' => $evento->tipoContingencia !== null ? (int) $evento->tipoContingencia : null,
                'motivoContin' => $this->nullable($evento->motivoContin),
                'codigoGeneracion' => strtoupper((string) $evento->codigoGeneracion),
                'fecEmi' => Carbon::parse($evento->fecEmi)->format('Y-m-d'),
                'horEmi' => Carbon::parse($evento->horEmi)->format('H:i:s'),
                'fusion' => $this->nullable($evento->fusion),
                'tipoMoneda' => 'USD',
            ],
            'documentoRelacionado' => [[
                'tipoDocumento' => str_pad((string) $dteVenta->RtipoDte->codigo, 2, '0', STR_PAD_LEFT),
                'codigoGeneracion' => strtoupper((string) $dteVenta->codigoGeneracion),
                'fechaEmision' => Carbon::parse($dteVenta->fecEmi)->format('Y-m-d'),
            ]],
            'emisor' => $this->emisor($evento),
            'documento' => $this->receptor($evento),
            'ventaTercero' => null,
            'compraTercero' => null,
            'cuerpoDocumento' => $items,
            'resumen' => [
                'totalNoSuj' => 0.0,
                'totalExenta' => 0.0,
                'totalGravada' => $totalGravada,
                'totalCompraExcluidos' => 0.0,
                'subTotalVentas' => $totalGravada,
                'tributos' => null,
                'totalSeguro' => 0.0,
                'totalFlete' => 0.0,
                'montoTotalOperacion' => $totalGravada,
                'ivaRete' => 0.0,
                'reteRenta' => 0.0,
                'totalNoGravado' => 0.0,
                'totalPagar' => $totalGravada,
                'totalLetras' => strtoupper(Convertidor::montoALetras($totalGravada)),
                'totalNoOnerosas' => 0.0,
                'totalIva' => $totalIva,
                'saldoFavor' => 0.0,
            ],
            'apendice' => null,
        ];
    }

    private function emisor(EventoRetorno $evento): array
    {
        $sucursal = $evento->Rsucursal;
        $empresa = $evento->Rempresa;
        $parametros = Parametros::find($evento->caja);

        return [
            'nit' => preg_replace('/\D+/', '', (string) $empresa->nit),
            // En este proyecto la razon social esta en empresas.razon y el
            // nombre comercial en empresas.empresa (igual que GeneraJsonF).
            'nombre' => trim((string) ($empresa->razon ?: $empresa->empresa)),
            'codEstableMH' => strtoupper(trim((string) ($sucursal->codEstableMH ?: 'M001'))),
            'codEstable' => str_pad((string) $sucursal->numero, 4, '0', STR_PAD_LEFT),
            'codPuntoVentaMH' => strtoupper(trim((string) (optional($parametros)->codPuntoVentaMH ?: 'P001'))),
            'codPuntoVenta' => str_pad((string) (optional($parametros)->caja ?: 1), 4, '0', STR_PAD_LEFT),
            // Solo aplican a Factura de Exportacion.
            'recintoFiscal' => null,
            'tipoRegimen' => null,
            'regimen' => null,
            'tipoItemExpor' => null,
        ];
    }

    /**
     * Receptor del evento. Primero se toma del JSON del DTE de origen (es el
     * mismo receptor que Hacienda ya tiene registrado); si ese JSON no esta o
     * no trae receptor, se arma desde el cliente de la venta.
     */
    private function receptor(EventoRetorno $evento): ?array
    {
        $desdeDte = $this->receptorDesdeDte($evento->Rdte);

        if ($desdeDte !== null) {
            return $desdeDte;
        }

        $cliente = $evento->Rcliente;

        if (!$cliente) {
            return null;
        }

        [$tipo, $numero] = $this->identificacionCliente($cliente);

        if ($numero === null && $this->nullable($cliente->nombreCliente) === null) {
            return null;
        }

        return [
            'tipoDocumento' => $numero !== null ? $tipo : null,
            'numDocumento' => $numero,
            'nombre' => $this->texto($cliente->nombreCliente, 250),
            // Pais solo se detalla en operaciones de exportacion.
            'codPais' => null,
            'nombrePais' => null,
            'telefono' => $this->telefono($cliente->telefono ?: $cliente->celular),
            'correo' => $this->correo($cliente->email),
        ];
    }

    /**
     * Receptor tal como salio en el DTE: "receptor" en Factura y Exportacion,
     * "sujetoExcluido" en la FSEE. jsonDte puede venir comprimido (gz64).
     */
    private function receptorDesdeDte(?dte $dteVenta): ?array
    {
        if (!$dteVenta || trim((string) $dteVenta->jsonDte) === '') {
            return null;
        }

        try {
            $datos = json_decode((string) JsonComprimido::descomprimir($dteVenta->jsonDte), true);
        } catch (\Throwable $e) {
            return null;
        }

        if (!is_array($datos)) {
            return null;
        }

        $r = $datos['receptor'] ?? $datos['sujetoExcluido'] ?? null;

        if (!is_array($r)) {
            return null;
        }

        $tipo = $this->nullable($r['tipoDocumento'] ?? null);
        $numero = $this->texto($r['numDocumento'] ?? null, 20);

        if ($numero === null && $this->nullable($r['nombre'] ?? null) === null) {
            return null;
        }

        return [
            'tipoDocumento' => $numero !== null ? $tipo : null,
            'numDocumento' => $numero,
            'nombre' => $this->texto($r['nombre'] ?? null, 250),
            'codPais' => $this->nullable($r['codPais'] ?? null),
            'nombrePais' => $this->nullable($r['nombrePais'] ?? null),
            'telefono' => $this->telefono($r['telefono'] ?? null),
            'correo' => $this->correo($r['correo'] ?? null),
        ];
    }

    /**
     * Tipo y numero de documento del cliente con el mismo criterio de la
     * Factura: el CAT-022 del cliente manda; DUI y NIT viajan como se guardan.
     */
    private function identificacionCliente(Clientes $cliente): array
    {
        $tipo = trim((string) optional($cliente->RidentificacionReceptor)->codigo);
        $dui = $this->nullable($cliente->dui);
        $nit = $this->nullable($cliente->nit);

        if ($tipo === '13') {
            return ['13', $dui ?? $nit];
        }

        if ($tipo === '36') {
            return $nit !== null ? ['36', $nit] : ['13', $dui];
        }

        if ($tipo !== '') {
            return [$tipo, $dui ?? $nit];
        }

        return $dui !== null ? ['13', $dui] : ['36', $nit];
    }

    /** El schema exige de 8 a 30 caracteres; un telefono incompleto viaja como null. */
    private function telefono($valor): ?string
    {
        $valor = $this->nullable($valor);

        if ($valor === null || mb_strlen($valor) < 8) {
            return null;
        }

        return mb_substr($valor, 0, 30);
    }

    private function correo($valor): ?string
    {
        if (!NormalizadorCorreoDte::esValido($valor)) {
            return null;
        }

        return mb_substr(NormalizadorCorreoDte::normalizar($valor), 0, 100);
    }

    private function texto($valor, int $maximo): ?string
    {
        $valor = $this->nullable($valor);

        return $valor === null ? null : mb_substr($valor, 0, $maximo);
    }

    // --------------------------------------------------------------- validacion

    private function validar(EventoRetorno $evento): void
    {
        if ($evento->Rdetalles->isEmpty()) {
            throw new RuntimeException('El evento de retorno no tiene items.');
        }

        // Relaciones sin las cuales no se puede armar el JSON. Se comprueban aqui
        // para dar un mensaje util en vez de "Attempt to read property on null"
        // desde el medio de la construccion.
        $obligatorias = [
            'Rventa' => 'la venta relacionada',
            'Rdte' => 'el DTE de la factura',
            'Rambiente' => 'el ambiente de destino',
            'Rsucursal' => 'la sucursal emisora',
            'Rempresa' => 'la empresa emisora',
        ];

        foreach ($obligatorias as $relacion => $descripcion) {
            if (!$evento->{$relacion}) {
                throw new RuntimeException('No se encontro ' . $descripcion . ' del evento.');
            }
        }

        if (!$evento->Rdte->RtipoDte) {
            throw new RuntimeException('El DTE de la factura no tiene tipo de documento asignado.');
        }

        $venta = $evento->Rventa;

        $errores = $this->motivosNoAplicable($venta, Carbon::parse($evento->fecEmi)->format('Y-m-d'));

        if ($errores) {
            throw new RuntimeException(implode(' ', $errores));
        }

        $monto = $this->dinero($evento->Rdetalles->sum('ventaGravada'));
        $previo = $this->dinero($this->retornadoPrevio($venta->id, $evento->id));
        $tope = $this->dinero($venta->total);

        if ($this->dinero($monto + $previo) > $tope) {
            throw new RuntimeException(
                'El monto a retornar (' . number_format($monto, 2) . ') mas lo ya retornado ('
                . number_format($previo, 2) . ') supera el total del DTE (' . number_format($tope, 2) . ').'
            );
        }
    }

    // ------------------------------------------------------------------ helpers

    private function cargar(int $id): EventoRetorno
    {
        return EventoRetorno::with([
            'Rdetalles',
            'Rventa',
            'Rdte.RtipoDte:id,codigo',
            'Rcliente.RidentificacionReceptor',
            'Rambiente:id,codigo',
            'Rsucursal',
            'Rempresa',
        ])->findOrFail($id);
    }

    private function tipoItem(): int
    {
        return (int) (TipoItem::where('status', 'Activo')->value('codigo') ?: 1);
    }

    private function nullable($valor): ?string
    {
        $valor = trim((string) $valor);

        return $valor === '' ? null : $valor;
    }

    private function dinero($valor): float
    {
        return round((float) $valor, 2);
    }

    private function numero($valor, int $decimales): float
    {
        return round((float) $valor, $decimales);
    }
}
