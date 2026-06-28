<?php

namespace App\Exports;

use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\{
    FromCollection,
    WithCustomCsvSettings
};

class AnexoContribuyenteExport implements FromCollection, WithCustomCsvSettings
{
    private $sales;

    public function __construct($sales)
    {
        $this->sales = $sales;
    }

    public function collection()
    {
        return $this->sales->map(function ($sale) {
            $esFisico = $sale->tipo === 'Fisico';

            $primerCodigo = str_replace('-', '', $sale->primer_codigo ?? '');
            $ultimoCodigo = str_replace('-', '', $sale->ultimo_codigo ?? '');
            $primerNumero = str_replace('-', '', $sale->primer_numero ?? '');
            $ultimoNumero = str_replace('-', '', $sale->ultimo_numero ?? '');

            return [
                // A. Fecha de emisión
                Carbon::parse($sale->fecha)->format('d/m/Y'),

                // B. Clase de documento
                $esFisico ? '1' : '4',

                // C. Tipo de documento
                $sale->facturador == 1 ? '10' : '10',

                // D. Número de resolución
                $esFisico
                    ? ($sale->facturador == 1 ? ($sale->Rcaja->tresolucion ?? 'SIN RESOLUCION') : ($sale->Rcaja->conresolucion ?? 'SIN RESOLUCION'))
                    : 'N/A',

                // E. Serie
                $esFisico
                    ? ($sale->facturador == 1 ? ($sale->Rcaja->tserie ?? 'SIN SERIE') : ($sale->Rcaja->conserie ?? 'SIN SERIE'))
                    : 'N/A',

                // F. Número de control (del)
                $esFisico ? ($sale->primer_correlativo ?? '') : $primerCodigo,

                // G. Número de control (al)
                $esFisico ? ($sale->ultimo_correlativo ?? '') : $ultimoCodigo,

                // H. Número de documento (del)
                $esFisico ? ($sale->primer_correlativo ?? '') : $primerNumero,

                // I. Número de documento (al)
                $esFisico ? ($sale->ultimo_correlativo ?? '') : $ultimoNumero,

                // J. Caja
                $esFisico && $sale->facturador != 2 ? ($sale->Rcaja->caja ?? '') : '',

                // K. Ventas exentas
                number_format((float) ($sale->ventasExenta ?? 0), 2, '.', ''),

                // L. Ventas internas exentas
                number_format((float) ($sale->ventasInternaExenta ?? 0), 2, '.', ''),

                // M. Ventas no sujetas
                number_format((float) ($sale->ventaNoSujera ?? 0), 2, '.', ''),

                // N. Ventas gravadas
                number_format((float) ($sale->ventaGravada ?? 0), 2, '.', ''),

                // O. Exportaciones dentro
                number_format((float) ($sale->exportacionesDentro ?? 0), 2, '.', ''),

                // P. Exportaciones fuera
                number_format((float) ($sale->exportacionesFuera ?? 0), 2, '.', ''),

                // Q. Exportaciones servicios
                number_format((float) ($sale->exportacionesServicios ?? 0), 2, '.', ''),

                // R. Ventas zona franca
                number_format((float) ($sale->ventasZonaFranca ?? 0), 2, '.', ''),

                // S. Ventas cuenta terceros
                number_format((float) ($sale->ventaCuentaTerceros ?? 0), 2, '.', ''),

                // T. Total ventas gravadas (puede repetir N si no hay otra lógica)
                number_format((float) ($sale->ventaGravada ?? 0), 2, '.', ''),

                // U. Tipo de operación
                '01',

                // V. Tipo de ingreso
                '03',

                // W. Anexo
                '2',
            ];
        });
    }

    public function getCsvSettings(): array
    {
        return [
            'delimiter'   => ';',
            'enclosure'   => '',
            'line_ending' => "\r\n",
        ];
    }
}
