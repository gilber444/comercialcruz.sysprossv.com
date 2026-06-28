<?php
namespace App\Traits;
use App\Models\dte;
use App\Models\resumenDte;
use Illuminate\Support\Facades\DB;
trait GeneraJsonS
{
    public function GeneraJsonS($id)
    { 
        // Identificación
        $identificacion = dte::join('ambiente_destinos as amb', 'amb.id', 'dtes.ambiente')
            ->join('modelo_facturacions as mfac', 'mfac.id', 'dtes.tipoModelo')
            ->join('tipo_transmisions as ttrans', 'ttrans.id', 'dtes.tipoOperacion')
            ->leftJoin('tipo_contigencias as tconti', 'tconti.id', 'dtes.tipoContingencia')
            ->select('dtes.id',
                'dtes.version', 'amb.codigo as ambiente',
                DB::raw("'14' as tipoDte"), // fijo como exige el esquema
                'dtes.numeroControl', 'dtes.codigoGeneracion',
                'mfac.codigo as tipoModelo', 'ttrans.codigo as tipoOperacion',
                'tconti.codigo as tipoContingencia', 'tconti.valor as motivoConti',
                'dtes.fecEmi as FechaEmi', 'dtes.horEmi as horaEmi',
                'dtes.tipoMoneda'
            )
            ->where('dtes.venta', $id)
            ->where('dtes.tipoDte', 10) // Asegura que es tipo 14
            ->first();
        // Emisor
        $emisor = dte::join('empresas as e', 'e.id', 'dtes.empresa')
        ->join('actividad_economicas as ace', 'ace.id', 'e.actividad')
        ->join('sucursales as s', 's.id', 'dtes.sucursal')
        ->join('tipo_establecimientos as testa', 'testa.id', 's.tipo')
        ->join('departamentos as d', 'd.id', 's.departamento')
        ->join('distritos as dis', 'dis.id', 's.distrito')
        ->select('e.nit', 'e.registro as nrc', 'e.razon as nombre', 'ace.codigo as codActividad', 'ace.valor as descActividad', 'e.empresa as nombreComercial', 'testa.codigo as tipoEstablecimiento', 'd.codigo as departamento', 'dis.codigo as municipio', 's.direccion as complemento', 's.telefono', 's.numero', 'e.correo')
        ->find($identificacion->id);
        // Receptor
        $receptor = dte::join('sujeto_excluidos as se', 'se.id', 'dtes.venta')
            ->join('clientes as c', 'c.id', 'se.cliente')
            ->join('departamentos as d', 'd.id', 'c.departamento')
            ->join('distritos as dis', 'dis.id', 'c.distrito')
            ->select(
                DB::raw("'13' as tipoDocumento"),
                'c.dui as numDocumento', 'c.nombreCliente as nombre',
                'd.codigo as departamento', 'dis.codigo as municipio',
                'c.direccion as complemento', 'c.telefono', 'c.email as correo'
            )
            ->find($identificacion->id) ?? (object)[
                'tipoDocumento' => '13',
                'numDocumento' => '',
                'nombre' => '',
                'departamento' => '',
                'municipio' => '',
                'complemento' => '',
                'telefono' => '',
                'correo' => '',
            ];
        // Detalles (usa leftJoin para evitar vacíos si falta producto o unidad)
        $detalleVentas = dte::join('sujeto_excluidos as se', 'se.id', 'dtes.venta')
            ->join('sujeto_excluidos_detalles as sed', 'sed.sujeto_excluido_id', 'se.id')
            ->leftJoin('productos as p', 'p.id', 'sed.producto')
            ->leftJoin('unidad_medidas as umh', 'umh.id', 'p.medidamh')
            ->select(
                'sed.cantidad',
                'p.codebar3 as codigo',
                'umh.codigo AS uniMedida',
                'sed.descripcion',
                'sed.precio_unitario as precioUni',
                'sed.descuento as montoDescu',
                'sed.ventas as compra'
            )
            ->where('dtes.id', $identificacion->id)
            ->get();
        // Resumen
        $detalleDte = resumenDte::where('dte', $identificacion->id)->first();
        $cuerpoDocumento = [];
        $numItem = 1;
        foreach ($detalleVentas as $detalle) {
            $item = [
                'numItem' => $numItem++,
                'tipoItem' => 1,
                'cantidad' => (float) $detalle->cantidad,
                'codigo' => $detalle->codigo ?? '',
                'uniMedida' => (float)$detalle->uniMedida,
                'descripcion' => $detalle->descripcion,
                'precioUni' => (float) $detalle->precioUni,
                'montoDescu' => (float) $detalle->montoDescu,
                'compra' => (float) $detalle->compra,
            ];
            $cuerpoDocumento[] = $item;
        }
        $datosArray = [
            'identificacion' => [
                'motivoContin' => $identificacion->motivoConti,
                'version' => (int) $identificacion->version,
                'ambiente' => $identificacion->ambiente,
                'tipoDte' => '14',
                'numeroControl' => $identificacion->numeroControl,
                'codigoGeneracion' => $identificacion->codigoGeneracion,
                'tipoModelo' => (int) $identificacion->tipoModelo,
                'tipoOperacion' => (int) $identificacion->tipoOperacion,
                'tipoContingencia' => $identificacion->tipoContingencia,
                'fecEmi' => $identificacion->FechaEmi,
                'horEmi' => $identificacion->horaEmi,
                'tipoMoneda' => $identificacion->tipoMoneda,
            ],
            'emisor' => [
                'nit' => $emisor->nit,
                'nrc' => $emisor->nrc,
                'nombre' => $emisor->nombre,
                'codActividad' => $emisor->codActividad, // deberías obtenerlo de BD
                'descActividad' => $emisor->descActividad, // idem
                'direccion' => [
                    'departamento' => $emisor->departamento,
                    'municipio' => $emisor->municipio,
                    'complemento' => $emisor->complemento,
                ],
                'telefono' => $emisor->telefono,
                'codEstableMH' => $emisor->codEstableMH, // reemplaza con real
                'codEstable' => NULL,
                'codPuntoVentaMH' => $emisor->codPuntoVentaMH, // reemplaza con real
                'codPuntoVenta' => $emisor->codPuntoVenta, // reemplaza con real
                'correo' => $emisor->correo,
            ],
            'sujetoExcluido' => [
                'tipoDocumento' => $receptor->tipoDocumento,
                'numDocumento' => preg_replace('/\D/', '', $receptor->numDocumento),
                'nombre' => $receptor->nombre,
                'codActividad' => null,
                'descActividad' => null,
                'direccion' => [
                    'departamento' => $receptor->departamento,
                    'municipio' => $receptor->municipio,
                    'complemento' => $receptor->complemento,
                ],
                'telefono' => $receptor->telefono,
                'correo' => $receptor->correo,
            ],
            'cuerpoDocumento' => $cuerpoDocumento,
            'resumen' => [
                'totalCompra' => (float) ($detalleDte->totalGravada + $detalleDte->ivaRete1 + $detalleDte->reteRenta ?? 0),
                'descu' => 0.00,
                'totalDescu' => 0.00,
                'subTotal' => (float) ($detalleDte->totalGravada + $detalleDte->ivaRete1 + $detalleDte->reteRenta ?? 0),
                'ivaRete1' =>  (float) ($detalleDte->ivaRete1 ?? 0),
                'reteRenta' => (float) ($detalleDte->reteRenta ?? 0),
                'totalPagar' => (float) ($detalleDte->totalGravada  ?? 0),
                'totalLetras' => $detalleDte->totalLetras ?? '',
                'condicionOperacion' => 1,
                'pagos' => [ 
                    [
                        'codigo' => '01',
                        'montoPago' => (float) (float) ($detalleDte->totalGravada ?? 0),
                        'referencia' => null,
                        'plazo' => null,
                        'periodo' => null
                    ]
                ],
                'observaciones' => null,
            ],
            'apendice' => null
        ];
        return json_encode($datosArray);
    }
}
