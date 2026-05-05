<?php

namespace App\Exports;

use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\{
    FromCollection,
    WithCustomCsvSettings
}; 

class AnexoContribuyentesExport implements FromCollection, WithCustomCsvSettings
{
    private $sales;

    public function __construct($sales)
    {
        $this->sales = $sales;
    }

    public function collection()
    {
        return $this->sales->map(function ($sale) {

            return [
                // A. Fecha de emisión
                Carbon::parse($sale->fecha)->format('d/m/Y'),

                // B. Clase de documento
                '4',

                // C. Tipo de documento
                '03',

                // D. Número de resolución
                str_replace('-', '', $sale->numero),

                // E. Serie
                str_replace('-', '', $sale->sello),

                // F. Número de control (del)
                str_replace('-', '', $sale->codigo),

                // G. Número de control (al)
                str_replace('-', '', $sale->codigo),

                // H. Número de documento (del)
                (isset($sale->Rclientes->nit) && strlen($sale->Rclientes->nit) === 14)
                    ? $sale->Rclientes->nit
                    : null,

                // I. Número de documento (al)
                $sale->Rclientes->nombreCliente,

                // J. Caja
                '0',

                // K. Ventas exentas
                '0',

                // L. Ventas internas exentas
                number_format((float) ($sale->total / 1.13), 2, '.', ''),

                // M. Ventas no sujetas
                number_format((float) $sale->total - ($sale->total / 1.13), 2, '.', ''),

                // N. Ventas gravadas
                '0',

                // O. Exportaciones dentro
                '0',

                // P. Exportaciones fuera
                number_format((float) ($sale->total), 2, '.', ''),

                // Q. Exportaciones servicios
                (isset($sale->Rclientes->nit) && strlen($sale->Rclientes->nit) === 9)
                    ? $sale->Rclientes->nit
                    : null,

                // R. Ventas zona franca
                '01',

                // V. Tipo de ingreso
                '03',

                // W. Anexo
                '1',
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
