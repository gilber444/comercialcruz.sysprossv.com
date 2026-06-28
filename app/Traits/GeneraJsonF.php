<?php
namespace App\Traits;
use App\Models\dte;
use App\Models\Parametros;
use App\Models\resumenDte;
use App\Models\TipoItem;
use Illuminate\Support\Facades\DB;
trait GeneraJsonF
{
    public function GeneraJsonF($id)
    {
        $identificacion = dte::join('ambiente_destinos as amb', 'amb.id', 'dtes.ambiente')->join('tipo_documentos as tdoc', 'tdoc.id', 'dtes.tipoDte')->join('modelo_facturacions as mfac', 'mfac.id', 'dtes.tipoModelo')->join('tipo_transmisions as ttrans', 'ttrans.id', 'dtes.tipoOperacion')->join('tipo_contigencias as tconti', 'tconti.id', 'dtes.tipoContingencia')->select('dtes.version', 'amb.codigo as ambiente', 'tdoc.codigo as tipoDte', 'dtes.numeroControl', 'dtes.codigoGeneracion', 'mfac.codigo as tipoModelo', 'ttrans.codigo as tipoOperacion', 'dtes.tipoContingencia', 'tconti.codigo as tipoContingencia', 'tconti.valor as motivoConti', 'dtes.fecEmi as FechaEmi', 'dtes.horEmi as horaEmi', 'dtes.tipoMoneda')->find($id);
        $emisor = dte::join('empresas as e', 'e.id', 'dtes.empresa')
            ->join('parametros as p', 'p.id', 'dtes.caja')
            ->join('actividad_economicas as ace', 'ace.id', 'e.actividad')
            ->join('sucursales as s', 's.id', 'dtes.sucursal')
            ->join('tipo_establecimientos as testa', 'testa.id', 's.tipo')
            ->join('departamentos as d', 'd.id', 's.departamento')
            ->join('distritos as dis', 'dis.id', 's.distrito')
            ->select(
                'e.nit',
                'e.registro as nrc',
                'e.razon as nombre',
                'ace.codigo as codActividad',
                'ace.valor as descActividad',
                'e.empresa as nombreComercial',
                'testa.codigo as tipoEstablecimiento',
                'd.codigo as departamento',
                'dis.codigo as municipio',
                's.direccion as complemento',
                's.telefono',
                's.numero',
                's.codEstableMH',
                'p.codPuntoVentaMH',
                'e.correo')
            ->find($id);
        $receptor = dte::join('ventas as v', 'v.id', 'dtes.venta')->join('clientes as c', 'c.id', 'v.cliente')->join('identificacion_receptors as irec', 'irec.id', 'c.idenReceptor')->join('departamentos as d', 'd.id', 'c.departamento')->join('distritos as dis', 'dis.id', 'c.distrito')->select('irec.codigo as tipoDocumento', 'c.dui as numDocumento', 'c.nombreCliente as nombre', 'c.telefono', 'c.email as correo', 'd.codigo as departamento', 'dis.codigo as municipio', 'c.direccion as complemento', 'v.caja')->find($id);
        $para = Parametros::find($receptor->caja);
        // Traes tu parámetro (incluye: tcorrelativo, envio, descuento…)
        $discountPercent  = $para->descuento;
        $discountFactor   = (100 - $discountPercent) / 100;
        $detalleVentas = dte::join('ventas as v', 'v.id', 'dtes.venta')
        ->join('ventas_detalles as vdeta', 'vdeta.venta', 'v.id')
        ->join('productos as p', 'p.id', 'vdeta.producto')
        ->join('unidad_medidas as umh', 'umh.id', 'p.medidamh')
        ->select(
            DB::raw("IF(v.facturador = 1, vdeta.total, vdeta.total * {$discountFactor}) AS ventaGravada"),
            'vdeta.cantidad',
            'p.codebar3 AS codigo',
            'umh.codigo AS uniMedida',
            'vdeta.name AS descripcion',
            DB::raw("IF(v.facturador = 1, vdeta.precio, vdeta.precio * {$discountFactor}) AS precioUni"),
            DB::raw("IF(v.facturador = 1, vdeta.descuento, vdeta.descuento * {$discountFactor}) AS montoDescu"),
            DB::raw("IF(v.facturador = 1, vdeta.iva, vdeta.iva * {$discountFactor}) AS ivaItem")
        )
        ->where('dtes.id', $id)
        ->get();
        $detalleDte = resumenDte::join('condicion_operacions as condi', 'condi.id', 'resumen_dtes.condicionOperacion')->join('forma_pagos as fpa', 'fpa.id', 'resumen_dtes.pagos')->where('dte', $id)->select('resumen_dtes.totalNoSuj', 'resumen_dtes.totalExenta', 'resumen_dtes.totalGravada', 'resumen_dtes.subTotalVentas', 'resumen_dtes.descuNoSuj', 'resumen_dtes.descuExenta', 'resumen_dtes.descuGravada', 'resumen_dtes.porcentajeDescuento', 'resumen_dtes.totalDescu', 'resumen_dtes.subTotal', 'resumen_dtes.ivaRete1', 'resumen_dtes.reteRenta', 'resumen_dtes.montoTotalOperacion', 'resumen_dtes.totalNoGravado', 'resumen_dtes.totalPagar', 'resumen_dtes.totalLetras', 'resumen_dtes.saldoFavor', 'condi.codigo as condicionOperacion', 'fpa.codigo', 'resumen_dtes.numPagoElectronico', 'resumen_dtes.totalIva')->first();
        $datosArray = [
            'identificacion' => [
                'motivoContin' => $identificacion->motivoConti,
                'version' => (int) $identificacion->version,
                'ambiente' => $identificacion->ambiente,
                'tipoDte' => $identificacion->tipoDte,
                'numeroControl' => $identificacion->numeroControl,
                'codigoGeneracion' => $identificacion->codigoGeneracion,
                'tipoModelo' => (int) $identificacion->tipoModelo,
                'tipoOperacion' => (int) $identificacion->tipoOperacion,
                'tipoContingencia' => $identificacion->tipoContingencia,
                'fecEmi' => $identificacion->FechaEmi,
                'horEmi' => $identificacion->horaEmi,
                'tipoMoneda' => $identificacion->tipoMoneda,
            ],
            'documentoRelacionado' => null,
            'emisor' => [
                'codEstableMH' => $emisor->codEstableMH,
                'codEstable' => null,
                'codPuntoVentaMH' => $emisor->codPuntoVentaMH,
                'codPuntoVenta' => $emisor->codPuntoVenta,
                'nit' => $emisor->nit,
                'nrc' => $emisor->nrc,
                'nombre' => $emisor->nombre,
                'codActividad' => $emisor->codActividad,
                'descActividad' => $emisor->descActividad,
                'nombreComercial' => $emisor->nombreComercial,
                'tipoEstablecimiento' => $emisor->tipoEstablecimiento,
                'direccion' => [
                    'departamento' => $emisor->departamento,
                    'municipio' => $emisor->municipio,
                    'complemento' => $emisor->complemento,
                ],
                'telefono' => $emisor->telefono,
                'correo' => $emisor->correo,
            ],
            'receptor' => [
                'tipoDocumento' => $receptor->tipoDocumento,
                'numDocumento' => $receptor->numDocumento,
                'nrc' => $receptor->nrc,
                'nombre' => $receptor->nombre,
                'codActividad' => $receptor->codActividad,
                'descActividad' => $receptor->descActividad,
                'direccion' => [
                    'departamento' => $receptor->departamento,
                    'municipio' => $receptor->municipio,
                    'complemento' => $receptor->complemento,
                ],
                'telefono' => $receptor->telefono,
                'correo' => $receptor->correo,
            ],
            'otrosDocumentos' => null,
            'ventaTercero' => null,
        ];
        $cuerpoDocumento = [];
        $numItem = 1;
        foreach ($detalleVentas as $detalle) {
            $tipoItem = TipoItem::where('status', 'Activo')->select('codigo as tipoItem')->first();
            $item = [
                'psv' => (float) $detalle->psv,
                'noGravado' => 0,
                'ivaItem' => (float) $detalle->ivaItem,
                'numItem' => $numItem,
                'tipoItem' => (int) $tipoItem->tipoItem,
                'numeroDocumento' => $detalle->numeroDocumento,
                'cantidad' => (float) $detalle->cantidad,
                'codigo' => $detalle->codigo,
                'codTributo' => $detalle->codTributo,
                'uniMedida' => (int) $detalle->uniMedida,
                'descripcion' => $detalle->descripcion,
                'precioUni' => (float) $detalle->precioUni -$detalle->montoDescu,
                'montoDescu' => (float) 0,
                'ventaNoSuj' => 0,
                'ventaExenta' => 0,
                'ventaGravada' => (float) $detalle->ventaGravada,
                'tributos' => $detalle->tributos,
            ];
            $cuerpoDocumento[] = $item;
            $numItem++;
        }
        $resumen = [
            'porcentajeDescuento' => (float) ($detalleDte->porcentajeDescuento ?? 0.00),
            'reteRenta' => (float) ($detalleDte->reteRenta ?? 0.00),
            'totalNoGravado' => (float) ($detalleDte->totalNoGravado ?? 0.00),
            'totalPagar' => (float) ($detalleDte->totalPagar ?? 0.00),
            'saldoFavor' => (float) ($detalleDte->saldoFavor ?? 0.00),
            'pagos' => null,
            'numPagoElectronico' => null,
            'totalIva' => (float) ($detalleDte->totalIva ?? 0.00),
            'totalNoSuj' => (float) ($detalleDte->totalNoSuj ?? 0.00),
            'totalExenta' => (float) ($detalleDte->totalExenta ?? 0.00),
            'totalGravada' => (float) ($detalleDte->totalGravada ?? 0.00),
            'subTotalVentas' => (float) ($detalleDte->subTotalVentas ?? 0.00),
            'descuNoSuj' => (float) ($detalleDte->descuNoSuj ?? 0.00),
            'descuExenta' => (float) ($detalleDte->descuExenta ?? 0.00),
            'descuGravada' => (float) ($detalleDte->descuGravada ?? 0.00),
            'totalDescu' => (float) ($detalleDte->totalDescu ?? 0.00),
            'tributos' => null,
            'subTotal' => (float) ($detalleDte->subTotal ?? 0.00),
            'ivaRete1' => (float) ($detalleDte->ivaRete1 ?? 0.00),
            'montoTotalOperacion' => (float) ($detalleDte->montoTotalOperacion ?? 0.00),
            'totalLetras' => ($detalleDte->totalLetras ?? 0.00),
            'condicionOperacion' => (int) ($detalleDte->condicionOperacion ?? 0.00),
        ];
        $extension = [
            'placaVehiculo' => null,
            'nombEntrega' => null,
            'docuEntrega' => null,
            'nombRecibe' => null,
            'docuRecibe' => null,
            'observaciones' => null,
        ];
        $apendice = null;
        $datosArray['cuerpoDocumento'] = $cuerpoDocumento;
        $datosArray['resumen'] = $resumen;
        $datosArray['extension'] = $extension;
        $datosArray['apendice'] = $apendice;
        $jsonString = json_encode($datosArray);
        $update = dte::find($id);
        $update->jsonDte = $jsonString;
        $update->save();
        return $jsonString;
    }
}
