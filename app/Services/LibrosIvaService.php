<?php

namespace App\Services;

use App\Models\Compras;
use App\Models\dte;
use App\Models\Empresas;
use App\Models\Sucursales;
use App\Models\Ventas;
use App\Support\AnexosF07;
use App\Support\Decimal;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Consultas UNICAS de los libros de IVA y sus anexos.
 *
 * La pantalla Livewire, el PDF y el CSV de cada libro llaman al mismo metodo,
 * asi que los filtros (empresa, ambiente, sello, estado) no pueden quedar
 * aplicados en un lugar y olvidados en otro: el conteo de filas de la
 * pantalla es por construccion el mismo del PDF y del anexo.
 *
 * Reglas comunes (prompt de libros, seccion 2.b):
 *  - Solo DTE FINALIZADOS: dtes.estado = 'PROCESADO' con sello no nulo ni
 *    vacio (la venta tambien debe tener sello).
 *  - Solo del ambiente de la empresa: dtes.ambiente = empresas.ambiente.
 *    Nunca se mezclan pruebas y produccion.
 *  - Libro de consumidor final: solo ventas DTE (decision del usuario).
 *
 * Ventas exentas y no sujetas: el negocio NO las genera (respuesta del
 * usuario). Se informan en 0.00 en libros y anexos; si algun dia las genera
 * hay que guardar el desglose por venta (venta_gravada/exenta/no_sujeta).
 *
 * Todas las filas se devuelven como arreglos simples para que Livewire 2 las
 * pueda serializar en sus propiedades publicas sin volver a consultar.
 */
class LibrosIvaService
{
    /** Ambiente (id de ambiente_destinos) configurado en la empresa. */
    public function ambienteEmpresa($empresa)
    {
        return Empresas::where('id', $empresa)->value('ambiente');
    }

    /**
     * Filtro comun de ventas: empresa, sucursal/caja opcionales (0 = todas),
     * periodo, estado y DTE finalizado del ambiente de la empresa.
     */
    private function ventasFinalizadas($empresa, $sucursal, $caja, $desde, $hasta): Builder
    {
        $ambiente = $this->ambienteEmpresa($empresa);

        return Ventas::query()
            ->where('ventas.empresa', $empresa)
            ->when((string) $sucursal !== '0' && $sucursal !== null && $sucursal !== '', fn ($q) => $q->where('ventas.sucursal', $sucursal))
            ->when((string) $caja !== '0' && $caja !== null && $caja !== '', fn ($q) => $q->where('ventas.caja', $caja))
            ->whereIn('ventas.estado', ['Cancelado', 'Credito'])
            ->where('ventas.tipo', 'DTE')
            ->whereNotNull('ventas.sello')
            ->where('ventas.sello', '!=', '')
            ->whereBetween('ventas.fecha', [$desde, $hasta])
            ->whereExists(function ($q) use ($ambiente) {
                $q->select(DB::raw(1))
                    ->from('dtes')
                    ->whereColumn('dtes.venta', 'ventas.id')
                    // Hacienda devuelve 'PROCESADO' (asi lo guardan los
                    // Recepcion*); no depender de la intercalacion de MySQL.
                    ->whereIn('dtes.estado', ['PROCESADO', 'Procesado'])
                    ->whereNotNull('dtes.sello')
                    ->where('dtes.sello', '!=', '')
                    ->where('dtes.ambiente', $ambiente);
            });
    }

    /**
     * Monto de la operacion con IVA incluido. El POS guarda en ventas.total el
     * monto a cobrar, que ya tiene RESTADA la retencion del 1% del cliente
     * GRANDE (la columna se llama percepcion, pero es retencion); la venta
     * gravada es total + percepcion.
     */
    private function montoOperacion($venta): string
    {
        return AnexosF07::monto(Decimal::add($venta->total ?? 0, $venta->percepcion ?? 0));
    }

    // ------------------------------------------------------------------
    // Ventas a consumidor final (libro + anexo 2)
    // ------------------------------------------------------------------

    /**
     * Agrupado por dia + sucursal + caja: el anexo 2 va por rangos del/al.
     * El primer y ultimo documento del rango se toman en el orden en que se
     * emitieron (correlativo, luego id); el codigo de generacion es un UUID y
     * un MIN()/MAX() de texto NO devolveria el primero ni el ultimo.
     */
    public function ventasConsumidor($empresa, $sucursal, $caja, $desde, $hasta): Collection
    {
        $ventas = $this->ventasFinalizadas($empresa, $sucursal, $caja, $desde, $hasta)
            ->whereIn('ventas.facturador', [1, 2])
            ->with(['Rsucursal:id,nombre', 'Rcajas:id,caja'])
            ->select('ventas.id', 'ventas.fecha', 'ventas.sucursal', 'ventas.caja', 'ventas.correlativo',
                'ventas.codigo', 'ventas.numero', 'ventas.total', 'ventas.percepcion')
            ->orderBy('ventas.fecha')
            ->orderBy('ventas.sucursal')
            ->orderBy('ventas.caja')
            ->orderBy('ventas.correlativo')
            ->orderBy('ventas.id')
            ->get();

        return $ventas
            ->groupBy(fn ($v) => substr((string) $v->fecha, 0, 10) . '|' . $v->sucursal . '|' . $v->caja)
            ->map(function (Collection $grupo) {
                $primera = $grupo->first();
                $ultima = $grupo->last();
                $montos = $grupo->map(fn ($v) => $this->montoOperacion($v))->all();
                $total = AnexosF07::suma($montos);

                return [
                    'fecha' => substr((string) $primera->fecha, 0, 10),
                    'sucursal_id' => (int) $primera->sucursal,
                    'sucursal' => (string) ($primera->Rsucursal->nombre ?? ''),
                    'caja_id' => (int) $primera->caja,
                    'caja' => (string) ($primera->Rcajas->caja ?? ''),
                    'documentos' => $grupo->count(),
                    'del_correlativo' => (string) $primera->correlativo,
                    'al_correlativo' => (string) $ultima->correlativo,
                    'del_codigo' => (string) $primera->codigo,
                    'al_codigo' => (string) $ultima->codigo,
                    'del_numero' => (string) $primera->numero,
                    'al_numero' => (string) $ultima->numero,
                    // El negocio no genera ventas exentas ni no sujetas.
                    'exentas' => '0.00',
                    'no_sujetas' => '0.00',
                    'gravadas' => $total,   // IVA incluido, como pide el anexo 2
                    'total' => $total,
                ];
            })
            ->values();
    }

    // ------------------------------------------------------------------
    // Ventas a contribuyentes (libro + anexo 1)
    // ------------------------------------------------------------------

    public function ventasContribuyentes($empresa, $sucursal, $caja, $desde, $hasta): Collection
    {
        return $this->ventasFinalizadas($empresa, $sucursal, $caja, $desde, $hasta)
            ->where('ventas.facturador', 3)
            ->with(['Rclientes:id,nombreCliente,registro,dui,nit', 'Rcajas:id,caja'])
            ->select('ventas.id', 'ventas.fecha', 'ventas.caja', 'ventas.cliente', 'ventas.correlativo',
                'ventas.numero', 'ventas.sello', 'ventas.codigo', 'ventas.total', 'ventas.percepcion')
            ->orderBy('ventas.fecha')
            ->orderBy('ventas.correlativo')
            ->orderBy('ventas.id')
            ->get()
            ->map(function ($v) {
                // Base por division y debito POR RESTA de valores redondeados:
                // L + M = P exacto en cada fila.
                $total = $this->montoOperacion($v);
                $gravada = AnexosF07::monto(Decimal::div($total, '1.13'));
                $debito = AnexosF07::monto(Decimal::sub($total, $gravada));

                return [
                    'id' => (int) $v->id,
                    'fecha' => substr((string) $v->fecha, 0, 10),
                    'caja' => (string) ($v->Rcajas->caja ?? ''),
                    'correlativo' => (string) $v->correlativo,
                    'numero_control' => (string) $v->numero,
                    'codigo' => (string) $v->codigo,
                    'sello' => (string) $v->sello,
                    'cliente' => (string) ($v->Rclientes->nombreCliente ?? ''),
                    'nit' => (string) ($v->Rclientes->nit ?? ''),
                    'nrc' => (string) ($v->Rclientes->registro ?? ''),
                    'dui' => (string) ($v->Rclientes->dui ?? ''),
                    'exentas' => '0.00',
                    'no_sujetas' => '0.00',
                    'gravada' => $gravada,
                    'debito' => $debito,
                    'total' => $total,
                    'retencion' => AnexosF07::monto($v->percepcion ?? 0),
                ];
            })
            ->values();
    }

    /**
     * Anexo 7: ventas donde el CLIENTE GRANDE retuvo el 1% (ventas.percepcion
     * > 0; se RESTA del total, por eso es retencion y no percepcion). Incluye
     * creditos fiscales y facturas, porque el POS aplica la retencion en ambos.
     * El monto sujeto es la base sin IVA.
     */
    public function retencionesClientes($empresa, $sucursal, $caja, $desde, $hasta): Collection
    {
        return $this->ventasFinalizadas($empresa, $sucursal, $caja, $desde, $hasta)
            ->whereIn('ventas.facturador', [2, 3])
            ->where('ventas.percepcion', '>', 0)
            ->with(['Rclientes:id,nombreCliente,dui,nit'])
            ->select('ventas.id', 'ventas.fecha', 'ventas.facturador', 'ventas.cliente', 'ventas.numero',
                'ventas.sello', 'ventas.codigo', 'ventas.total', 'ventas.percepcion')
            ->orderBy('ventas.fecha')
            ->orderBy('ventas.id')
            ->get()
            ->map(function ($v) {
                $total = $this->montoOperacion($v);

                return [
                    'id' => (int) $v->id,
                    'fecha' => substr((string) $v->fecha, 0, 10),
                    'facturador' => (int) $v->facturador,
                    'numero_control' => (string) $v->numero,
                    'codigo' => (string) $v->codigo,
                    'sello' => (string) $v->sello,
                    'cliente' => (string) ($v->Rclientes->nombreCliente ?? ''),
                    'nit' => (string) ($v->Rclientes->nit ?? ''),
                    'dui' => (string) ($v->Rclientes->dui ?? ''),
                    'monto_sujeto' => AnexosF07::monto(Decimal::div($total, '1.13')),
                    'retencion' => AnexosF07::monto($v->percepcion),
                ];
            })
            ->values();
    }

    // ------------------------------------------------------------------
    // Invalidaciones
    // ------------------------------------------------------------------

    /**
     * DTE invalidados: tuvieron sello de Hacienda y son del ambiente de la
     * empresa. $tipoDte = 0 omite el filtro (opcion "Todos").
     */
    public function invalidaciones($empresa, $sucursal, $tipoDte, $desde, $hasta): Collection
    {
        $ambiente = $this->ambienteEmpresa($empresa);

        return dte::with(['RtipoDte', 'Rventas:id,correlativo,total,percepcion'])
            ->where('empresa', $empresa)
            ->when((string) $sucursal !== '0' && $sucursal !== null && $sucursal !== '', fn ($q) => $q->where('sucursal', $sucursal))
            ->when((string) $tipoDte !== '0' && $tipoDte !== null && $tipoDte !== '', fn ($q) => $q->where('tipoDte', $tipoDte))
            ->where('estado', 'INVALIDADO')
            ->where('ambiente', $ambiente)
            ->whereNotNull('sello')
            ->where('sello', '!=', '')
            ->whereBetween('fecEmi', [$desde, $hasta])
            ->orderBy('fecEmi')
            ->orderBy('horEmi')
            ->orderBy('numeroControl')
            ->get()
            ->map(function ($d) {
                $venta = $d->Rventas;

                return [
                    'id' => (int) $d->id,
                    'fecha' => substr((string) $d->fecEmi, 0, 10),
                    'hora' => (string) $d->horEmi,
                    'tipo_codigo' => (string) ($d->RtipoDte->codigo ?? ''),
                    'tipo_nombre' => (string) ($d->RtipoDte->valor ?? ''),
                    'numero_control' => (string) $d->numeroControl,
                    'codigo' => (string) $d->codigoGeneracion,
                    'sello' => (string) $d->sello,
                    'correlativo' => (string) ($venta->correlativo ?? ''),
                    'total' => $venta ? $this->montoOperacion($venta) : '0.00',
                ];
            })
            ->values();
    }

    // ------------------------------------------------------------------
    // Compras (libro + anexo 3)
    // ------------------------------------------------------------------

    /**
     * Compras del periodo de la empresa. Las compras anteriores a la
     * migracion 2026_08_08_120000 no tienen empresa: se reconocen por la
     * sucursal, que si pertenece a una empresa.
     *
     * La fecha de la compra es DATETIME: se compara por fecha para no perder
     * las compras del ultimo dia.
     */
    public function compras($empresa, $sucursal, $desde, $hasta): Collection
    {
        $sucursalesEmpresa = Sucursales::where('empresa', $empresa)->pluck('id')->all();
        // Base sin la migracion (PC local): no existe compras.empresa y el
        // aislamiento se hace solo por las sucursales de la empresa.
        $tieneEmpresa = Schema::hasColumn('compras', 'empresa');

        return Compras::with(['Rproveedores:id,nombre,nit,registro'])
            ->where(function ($q) use ($empresa, $sucursalesEmpresa, $tieneEmpresa) {
                if (!$tieneEmpresa) {
                    $q->whereIn('sucursal', $sucursalesEmpresa ?: [0]);
                    return;
                }
                $q->where('empresa', $empresa)
                    ->orWhere(function ($q2) use ($sucursalesEmpresa) {
                        $q2->whereNull('empresa')->whereIn('sucursal', $sucursalesEmpresa ?: [0]);
                    });
            })
            ->when((string) $sucursal !== '0' && $sucursal !== null && $sucursal !== '', fn ($q) => $q->where('sucursal', $sucursal))
            ->whereDate('fecha', '>=', $desde)
            ->whereDate('fecha', '<=', $hasta)
            ->orderBy('fecha')
            ->orderBy('id')
            ->get()
            ->map(function ($c) {
                $tipo = (string) ($c->tipo_documento ?? '');
                $clase = (string) ($c->clase_documento ?: (AnexosF07::esCodigoGeneracion($c->codigo_generacion ?: $c->correlativo) ? '4' : '1'));
                $codigo = $c->codigo_generacion ?: (AnexosF07::esCodigoGeneracion($c->correlativo) ? $c->correlativo : null);

                // Columna D: codigo de generacion (DTE) o correlativo impreso.
                $documento = $codigo
                    ? AnexosF07::documentoDte($c->fecha, $codigo, $c->numero_resolucion)
                    : AnexosF07::texto((string) $c->correlativo, 100);

                $total = AnexosF07::monto($c->total_compra ?? 0);

                return [
                    'id' => (int) $c->id,
                    'fecha' => substr((string) $c->fecha, 0, 10),
                    'clase_documento' => $clase,
                    'tipo_documento' => $tipo,
                    'correlativo' => (string) $c->correlativo,
                    'serie' => (string) $c->serie,
                    'documento' => $documento,
                    'proveedor' => (string) ($c->Rproveedores->nombre ?? ''),
                    'nit' => (string) ($c->Rproveedores->nit ?? ''),
                    'nrc' => (string) ($c->Rproveedores->registro ?? ''),
                    'compras_exentas' => AnexosF07::monto($c->compras_exentas ?? 0),
                    'internaciones_exentas' => AnexosF07::monto($c->internaciones_exentas ?? 0),
                    'importaciones_exentas' => AnexosF07::monto($c->importaciones_exentas ?? 0),
                    'compras_gravadas' => AnexosF07::monto($c->compras_gravadas ?? 0),
                    'internaciones_gravadas' => AnexosF07::monto($c->internaciones_gravadas ?? 0),
                    'importaciones_gravadas' => AnexosF07::monto($c->importaciones_gravadas ?? 0),
                    'importaciones_servicios' => AnexosF07::monto($c->importaciones_servicios ?? 0),
                    'credito_fiscal' => AnexosF07::monto($c->credito_fiscal ?? 0),
                    'total_compra' => $total,
                    'renta_tipo_operacion' => (string) ($c->renta_tipo_operacion ?? ''),
                    'renta_clasificacion' => (string) ($c->renta_clasificacion ?? ''),
                    'renta_sector' => (string) ($c->renta_sector ?? ''),
                    'renta_tipo_costo_gasto' => (string) ($c->renta_tipo_costo_gasto ?? ''),
                    // Compras sin desglose (anteriores a la migracion o sin
                    // tipo de documento): saldrian en cero en el anexo.
                    'sin_desglose' => $tipo === '' || (float) $total <= 0,
                    // El anexo 3 solo admite CCF, notas, factura de
                    // exportacion, declaracion de mercancias y mandamiento.
                    'en_anexo' => in_array($tipo, AnexosF07::ANEXO3_TIPOS_DOCUMENTO, true) && (float) $total > 0,
                ];
            })
            ->values();
    }

    // ------------------------------------------------------------------
    // Utilidades
    // ------------------------------------------------------------------

    /** Totales de columnas: suma de los valores ya redondeados de cada fila. */
    public function totales(iterable $filas, array $campos): array
    {
        $filas = collect($filas);
        $totales = [];

        foreach ($campos as $campo) {
            $totales[$campo] = AnexosF07::suma($filas->pluck($campo)->all());
        }

        return $totales;
    }

    /**
     * Empresas que puede consultar el usuario: Super y Contador ven todas; el
     * resto solo la propia (aislamiento por contribuyente).
     */
    public function empresasPermitidas($user): Collection
    {
        if (in_array($user->profile ?? null, ['Super', 'Contador'], true)) {
            return Empresas::all();
        }

        return Empresas::where('id', $user->empresa ?? 0)->get();
    }

    public function puedeVerEmpresa($user, $empresa): bool
    {
        return in_array($user->profile ?? null, ['Super', 'Contador'], true)
            || (int) ($user->empresa ?? 0) === (int) $empresa;
    }
}
