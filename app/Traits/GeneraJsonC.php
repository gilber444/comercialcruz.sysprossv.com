<?php
namespace App\Traits;
use App\Models\dte;
use App\Models\resumenDte;
use App\Models\TipoItem;
trait GeneraJsonC
{
    public function GeneraJsonC($id)
    {
        $dte = dte::join('ambiente_destinos as amb', 'amb.id', 'dtes.ambiente')->join('tipo_documentos as tdoc', 'tdoc.id', 'dtes.tipoDte')->join('modelo_facturacions as mfac', 'mfac.id', 'dtes.tipoModelo')->join('tipo_transmisions as ttrans', 'ttrans.id', 'dtes.tipoOperacion')->join('tipo_contigencias as tconti', 'tconti.id', 'dtes.tipoContingencia')->select('dtes.version', 'amb.codigo as ambiente', 'tdoc.codigo as tipoDte', 'dtes.numeroControl', 'dtes.codigoGeneracion', 'mfac.codigo as tipoModelo', 'ttrans.codigo as tipoOperacion', 'dtes.tipoContingencia', 'tconti.codigo as tipoContingencia', 'tconti.valor as motivoConti', 'dtes.fecEmi as FechaEmi', 'dtes.horEmi as horaEmi', 'dtes.tipoMoneda')->find($id);
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
        $receptor = dte::join('ventas as v', 'v.id', 'dtes.venta')->join('clientes as c', 'c.id', 'v.cliente')->join('identificacion_receptors as irec', 'irec.id', 'c.idenReceptor')->join('departamentos as d', 'd.id', 'c.departamento')->join('distritos as dis', 'dis.id', 'c.distrito')->join('actividad_economicas as ace', 'ace.id', 'c.actividad')->select('irec.codigo as tipoDocumento', 'c.dui', 'c.nit', 'c.registro', 'c.nombreCliente as nombre', 'c.telefono', 'c.email as correo', 'd.codigo as departamento', 'dis.codigo as municipio', 'c.direccion as complemento', 'c.idenReceptor', 'ace.codigo as codActividad', 'ace.valor as descActividad')->find($id);
        $detalleVentas = dte::join('ventas as v', 'v.id', 'dtes.venta')->join('ventas_detalles as vdeta', 'vdeta.venta', 'v.id')->join('productos as p', 'p.id', 'vdeta.producto')->join('unidad_medidas as umh', 'umh.id', 'p.medidamh')->select('vdeta.total as ventaGravada', 'vdeta.cantidad', 'p.codebar3 as codigo', 'umh.codigo as uniMedida', 'vdeta.name as descripcion', 'vdeta.precio as precioUni', 'vdeta.descuento as montoDescu', 'vdeta.iva as ivaItem')->where('dtes.id', $id)->get();
        $detalleDte = resumenDte::join('condicion_operacions as condi', 'condi.id', 'resumen_dtes.condicionOperacion')->join('forma_pagos as fpa', 'fpa.id', 'resumen_dtes.pagos')->where('dte', $id)->select('resumen_dtes.totalNoSuj', 'resumen_dtes.totalExenta', 'resumen_dtes.totalGravada', 'resumen_dtes.subTotalVentas', 'resumen_dtes.descuNoSuj', 'resumen_dtes.descuExenta', 'resumen_dtes.descuGravada', 'resumen_dtes.porcentajeDescuento', 'resumen_dtes.totalDescu', 'resumen_dtes.subTotal', 'resumen_dtes.ivaRete1', 'resumen_dtes.reteRenta', 'resumen_dtes.montoTotalOperacion', 'resumen_dtes.totalNoGravado', 'resumen_dtes.totalPagar', 'resumen_dtes.totalLetras', 'resumen_dtes.saldoFavor', 'condi.codigo as condicionOperacion', 'fpa.codigo', 'resumen_dtes.numPagoElectronico', 'resumen_dtes.totalIva', 'resumen_dtes.ivaPerci1')->first();
         $identificacion = [
            'version' => (int) $dte->version,
            'ambiente' => $dte->ambiente,
            'tipoDte' => $dte->tipoDte,
            'numeroControl' => $dte->numeroControl,
            'codigoGeneracion' => $dte->codigoGeneracion,
            'tipoModelo' => (int) $dte->tipoModelo,
            'tipoOperacion' => (int) $dte->tipoOperacion,
            'tipoContingencia' => $dte->tipoContingencia,
            'motivoContin' => $dte->motivoConti,
            'fecEmi' => $dte->FechaEmi,
            'horEmi' => $dte->horaEmi,
            'tipoMoneda' => $dte->tipoMoneda,
        ];
        $emisor = [
            'nit' => $emisor->nit,
            'nrc' => $emisor->nrc,
            'nombre' => $emisor->nombre,
            'codActividad' => $emisor->codActividad,
            'descActividad' => $emisor->descActividad,
            'nombreComercial' => $dte->nombreComercial,
            'tipoEstablecimiento' => $emisor->tipoEstablecimiento,
            'direccion' => [
                'departamento' => $emisor->departamento,
                'municipio' => $emisor->municipio,
                'complemento' => $emisor->complemento,
            ],
            'telefono' => $emisor->telefono,
            'codEstableMH' => $emisor->codEstableMH,
            'codEstable' => null,
            'codPuntoVentaMH' => $emisor->codPuntoVentaMH,
            'codPuntoVenta' => $emisor->codPuntoVenta,
            'correo' => $emisor->correo
        ];
        if($receptor->idenReceptor == 1)
        {
            $numDocumento = $receptor->nit;
        }
        else if($receptor->idenReceptor == 2)
        {
         $numDocumento = $receptor->dui;
        }
        $receptor = [
            'nit' => $numDocumento,
            'nrc' => $receptor->registro,
            'nombre' => $receptor->nombre,
            'codActividad' => $receptor->codActividad,
            'descActividad' => $receptor->descActividad,
            'nombreComercial' => $receptor->nombre,
            'direccion' => [
                'departamento' => $receptor->departamento,
                'municipio' => $receptor->municipio,
                'complemento' => $receptor->complemento,
            ],
            'telefono' => $receptor->telefono,
            'correo' => $receptor->correo,
        ];
        $cuerpoDocumento = [];
        $numItem = 1;
        foreach ($detalleVentas as $detalle) {
            $tipoItem = TipoItem::where('status', 'Activo')->select('codigo as tipoItem')->first();
            $item = [
                'numItem' => $numItem,
                'tipoItem' => (int) $tipoItem->tipoItem,
                'numeroDocumento' => $detalle->numeroDocumento,
                'cantidad' => (float) $detalle->cantidad,
                'codigo' => $detalle->codigo,
                'codTributo' => $detalle->codTributo,
                'uniMedida' => (int) $detalle->uniMedida,
                'descripcion' => $detalle->descripcion,
                'precioUni' => (float) round($detalle->precioUni / 1.13, 4),
                'montoDescu' => (float) round($detalle->montoDescu / 1.13, 4),
                'ventaNoSuj' => 0,
                'ventaExenta' => 0,
                'ventaGravada' => (float) round($detalle->ventaGravada / 1.13,4),
                'tributos' => [ '20' ],
                'psv' => (float) $detalle->psv,
                'noGravado' => 0,
            ];
            $cuerpoDocumento[] = $item;
            $numItem++;
        }
        $resumen = [
            'totalNoSuj' => (float) $detalleDte->totalNoSuj,
            'totalExenta' => (float) $detalleDte->totalExenta,
            'totalGravada' => (float) round($detalleDte->totalGravada - $detalleDte->totalIva, 2),
            'subTotalVentas' => (float) round($detalleDte->subTotalVentas - $detalleDte->totalIva, 2),
            'descuNoSuj' => (float) $detalleDte->descuNoSuj,
            'descuExenta' => (float) $detalleDte->descuExenta,
            'descuGravada' => (float) $detalleDte->descuGravada,
            'porcentajeDescuento' => (float) $detalleDte->porcentajeDescuento,
            'totalDescu' => (float) $detalleDte->totalDescu,
            'tributos' => [
                [
                    'codigo' => '20',
                    'descripcion' => 'Impuesto al Valor Agregado 13%',
                    'valor' => (float) $detalleDte->totalIva
                ]
            ],
            'subTotal' => (float) round($detalleDte->subTotal-$detalleDte->totalIva, 2),
            'ivaPerci1' => (float) $detalleDte->ivaPerci1,
            'ivaRete1' => (float) $detalleDte->ivaRete1,
            'reteRenta' => (float) $detalleDte->reteRenta,
            'montoTotalOperacion' => (float) $detalleDte->montoTotalOperacion ,
            'totalNoGravado' => (float) $detalleDte->totalNoGravado,
            'totalPagar' => (float) round($detalleDte->totalPagar, 2),
            'totalLetras' => $detalleDte->totalLetras,
            'saldoFavor' => (float) $detalleDte->saldoFavor,
            'condicionOperacion' => (int) $detalleDte->condicionOperacion,
            'pagos' => [
                [
                    'codigo' => '01',
                    'montoPago' => (float) round($detalleDte->totalPagar, 2),
                    'referencia' => null,
                    'periodo' => null,
                    'plazo' => '01',
                    'periodo'=> 30
                ],
            ],
            'numPagoElectronico' => null
        ];
        $extension = [
            'nombEntrega' => null,
            'docuEntrega' => null,
            'nombRecibe' => null,
            'docuRecibe' => null,
            'observaciones' => null,
            'placaVehiculo' => null,
        ];
        $apendice = null;
        $json = [
            'identificacion' => $identificacion,
            'documentoRelacionado' => null,
            'emisor' => $emisor,
            'receptor' => $receptor,
            'otrosDocumentos' => null,
            'ventaTercero' => null,
            'cuerpoDocumento' => $cuerpoDocumento,
            'resumen' => $resumen,
            'extension' => $extension,
            'apendice' => $apendice
        ];
        $jsonString = json_encode($json);
        $update = dte::find($id);
        $update->jsonDte = $jsonString;
        $update->save();
        return json_encode($json);
    }
}
