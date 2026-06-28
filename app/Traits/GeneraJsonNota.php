<?php
namespace App\Traits;
use App\Models\dte;
use App\Models\VentasDetalles;
use App\Models\NotaCredito;
use App\Models\DetalleNotaCredito;
use App\Models\TipoItem;
use App\Models\resumenDte;
use Carbon\Carbon;
trait GeneraJsonNota
{
    public function GeneraJsonNota($id)
    {
        $dte = Dte::with([
            'Rambiente:id,codigo',
            'RtipoDte:id,codigo',
            'RtipoModelo:id,codigo',
            'RtipoOperacion:id,codigo',
            'RtipoContingencia:id,codigo,valor',
            'Remisor.Rempresa',
            'Remisor.Rempresa.Ractividad:id,codigo,valor',
            'Remisor.RtipoEstable:id,codigo',
            'Remisor.Rdepartamento:id,codigo',
            'Remisor.Rdistrito:id,codigo',
            'Rventas:id,cliente,caja,facturador',
            'Rventas.Rclientes:id,nombreCliente,nit,registro,email,telefono,direccion,distrito,departamento,idenReceptor,actividad',
            'Rventas.Rclientes.RidentificacionReceptor:id,codigo',
            'Rventas.Rclientes.Rdepartamento:id,codigo',
            'Rventas.Rclientes.Rdistrito:id,codigo',
            'Rventas.Rclientes.RactividadEconomica:id,codigo,valor',
        ])->findOrFail($id);
        $venta = $dte->Rventas;
        $nota = NotaCredito::where('codigo', $dte->codigoGeneracion)->firstOrFail();
        $facturaRelacionada = dte::with('RtipoDte:id,codigo')->where('venta', $nota->venta)->firstOrFail();
        $detalleNota = DetalleNotaCredito::with(['Rproductos:id,codebar3,medidamh', 'Rproductos.RmedidasMH:id,codigo'])->where('nota_credito', $nota->id)->get();
        $cuerpoDocumento = [];
        $numItem = 1;
        foreach ($detalleNota as $detalle) {
            $tipoItem = TipoItem::where('status', 'Activo')->select('codigo as tipoItem')->first();
            $cuerpoDocumento[] = [
                'numItem' => $numItem,
                'tipoItem' => (int) $tipoItem->tipoItem,
                'numeroDocumento' => trim((string) $facturaRelacionada->codigoGeneracion),
                'cantidad' => (float) $detalle->cantidad,
                'codigo' => $detalle->codigo,
                'codTributo' => $detalle->codTributo,
                'uniMedida' => (int) $detalle->Rproductos->RmedidasMH->codigo,
                'descripcion' => $detalle->name,
                'precioUni' => (float) round($detalle->precio, 4),
                'montoDescu' => (float) round($detalle->descuento, 4),
                'ventaNoSuj' => 0,
                'ventaExenta' => 0,
                'ventaGravada' => (float) round($detalle->total, 4),
                'tributos' => ['20']
            ];
        }
        $detalleDte = resumenDte::with(['RcondicionOperacion:id,codigo', 'Rpagos:id,codigo'])
            ->where('dte', $id)->firstOrFail();
        $datosArray = [
            'identificacion' => [
                'motivoContin' => $dte->RtipoContingencia->valor,
                'version' => (int) 3,
                'ambiente' => $dte->Rambiente->codigo,
                //'ambiente' => "00",
                'tipoDte' => trim((string) "05"),
                'numeroControl' => $dte->numeroControl,
                'codigoGeneracion' => $dte->codigoGeneracion,
                'tipoModelo' => (int) $dte->RtipoModelo->codigo,
                'tipoOperacion' => (int) $dte->RtipoOperacion->codigo,
                'tipoContingencia' => $dte->RtipoContingencia->codigo,
                'fecEmi' => $dte->fecEmi,
                'horEmi' => $dte->horEmi,
                'tipoMoneda' => $dte->tipoMoneda,
            ],
            "documentoRelacionado" => [
                [
                    "tipoDocumento" => trim((string) "03"),
                    "tipoGeneracion" => (int) 2,
                    "numeroDocumento" => trim((string) $facturaRelacionada->codigoGeneracion),
                    "fechaEmision" => Carbon::parse($facturaRelacionada->fecEmi)->format('Y-m-d')
                ]
            ],
            'emisor' => [
                'nit' => $dte->Remisor->Rempresa->nit,
                'nrc' => $dte->Remisor->Rempresa->registro,
                'nombre' => $dte->Remisor->Rempresa->empresa,
                'codActividad' => $dte->Remisor->Rempresa->Ractividad->codigo,
                'descActividad' => $dte->Remisor->Rempresa->Ractividad->valor,
                'nombreComercial' => $dte->Remisor->Rempresa->razon,
                'tipoEstablecimiento' => $dte->Remisor->RtipoEstable->codigo,
                'direccion' => [
                    'departamento' => $dte->Remisor->Rdepartamento->codigo,
                    'municipio' => $dte->Remisor->Rdistrito->codigo,
                    'complemento' => $dte->Remisor->direccion,
                ],
                'telefono' => $dte->Remisor->telefono,
                'correo' => $dte->Remisor->Rempresa->correo
            ],
            'receptor' => [
                //'tipoDocumento' => $dte->Rventas->Rclientes->RidentificacionReceptor->codigo,
                'nit' => $dte->Rventas->Rclientes->nit,
                'nrc' => $dte->Rventas->Rclientes->registro,
                'nombre' => $dte->Rventas->Rclientes->nombreCliente,
                'codActividad' => $dte->Rventas->Rclientes->RactividadEconomica->codigo,
                'descActividad' => $dte->Rventas->Rclientes->RactividadEconomica->valor,
                'nombreComercial' => $dte->Rventas->Rclientes->nombreCliente,
                'direccion' => [
                    'departamento' => $dte->Rventas->Rclientes->Rdepartamento->codigo,
                    'municipio' => $dte->Rventas->Rclientes->Rdistrito->codigo,
                    'complemento' => $dte->Rventas->Rclientes->direccion,
                ],
                'telefono' => $dte->Rventas->Rclientes->telefono,
                'correo' => $dte->Rventas->Rclientes->email,
            ],
            'ventaTercero' => null,
            'cuerpoDocumento' => $cuerpoDocumento,
            'resumen' => [
                'reteRenta' => (float) ($detalleDte->reteRenta ?? 0.00),
                'totalNoSuj' => (float) ($detalleDte->totalNoSuj ?? 0.00),
                'totalExenta' => (float) ($detalleDte->totalExenta ?? 0.00),
                'totalGravada' => (float) (round(($detalleDte->totalGravada + $detalleDte->ivaRete1) - $detalleDte->totalIva,2) ?? 0.00),
                'subTotalVentas' => (float) (round($detalleDte->subTotalVentas + $detalleDte->ivaRete1 - $detalleDte->totalIva  ,2) ?? 0.00),
                'descuNoSuj' => (float) ($detalleDte->descuNoSuj ?? 0.00),
                'descuExenta' => (float) ($detalleDte->descuExenta ?? 0.00),
                'descuGravada' => (float) ($detalleDte->descuGravada ?? 0.00),
                'totalDescu' => (float) ($detalleDte->totalDescu ?? 0.00),
                'tributos' => [
                    [
                        'codigo' => '20',
                        'descripcion' => 'Impuesto al Valor Agregado 13%',
                        'valor' => (float) round($detalleDte->totalIva,2) ?? 0.00,
                    ]
                ],
                'subTotal' => (float) round(($detalleDte->subTotalVentas + $detalleDte->ivaRete1) - $detalleDte->totalIva,2) ?? 0.00,
                'ivaPerci1' => 0.00,
                'ivaRete1' => (float) ($detalleDte->ivaRete1 ?? 0.00),
                'reteRenta' => (float) $detalleDte->reteRenta,
                'montoTotalOperacion' => (float) round($detalleDte->totalPagar,2) ?? 0.00,
                'totalLetras' => $detalleDte->totalLetras,
                'condicionOperacion' => (int) 1,
            ],
            'extension' => [
                'nombEntrega'   => null,
                'docuEntrega'   => null,
                'nombRecibe'    => null,
                'docuRecibe'    => null,
                'observaciones' => null,
            ],
            'apendice' => null,
        ];
        $jsonString = json_encode($datosArray);
        $dte->jsonDte = $jsonString;
        $dte->save();
        return $jsonString;
    }
}
