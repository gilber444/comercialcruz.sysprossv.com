<?php

namespace App\Exports;

use App\Models\VentasDetalles;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class ReportUtilidadExport implements FromCollection, WithHeadings, WithTitle, WithCustomStartCell, WithStyles
{
    protected $sucursal, $caja, $reportType, $facturador, $dateFrom, $dateTo;

    function __construct($sucursal, $caja, $reportType, $facturador, $f1 = null, $f2 = null)
    {
        $this->sucursal = $sucursal;
        $this->caja = $caja;
        $this->reportType = $reportType;
        $this->facturador = $facturador;
        $this->dateFrom = $f1;
        $this->dateTo = $f2;
    }

    public function collection()
    {
        if ($this->reportType == 0) {
            $from = Carbon::now()->format('Y-m-d');
            $to = Carbon::now()->format('Y-m-d');
        } else {
            $from = $this->dateFrom;
            $to = $this->dateTo;
        }

        $query = VentasDetalles::join('ventas', 'ventas.id', 'ventas_detalles.venta')
            ->join('sucursales as s', 's.id', 'ventas.sucursal')
            ->join('facturadores as f', 'f.id', 'ventas.facturador')
            ->join('productos as p', 'p.id', 'ventas_detalles.producto')
            ->whereBetween('ventas.fecha', [$from, $to])
            ->where('ventas.estado', 'Cancelado');

        if ($this->sucursal != 0) $query->where('ventas.sucursal', $this->sucursal);
        if ($this->caja != 0) $query->where('ventas.caja', $this->caja);
        if ($this->facturador != 0) $query->where('ventas.facturador', $this->facturador);

        $rows = $query->select(
            's.nombre as sucursal',
            'f.facturador as facturador',
            'p.codebar3 as codebar',
            'p.nombreProducto',
            'ventas_detalles.cantidad',
            'ventas_detalles.costo',
            DB::raw('(ventas_detalles.cantidad * ventas_detalles.costo) as costo_total'),
            'ventas_detalles.precio',
            'ventas_detalles.total as total_venta',
            DB::raw('(ventas_detalles.total - (ventas_detalles.cantidad * ventas_detalles.costo)) as utilidad_monto'),
            DB::raw('CASE WHEN (ventas_detalles.cantidad * ventas_detalles.costo) > 0
                THEN ROUND(((ventas_detalles.total - (ventas_detalles.cantidad * ventas_detalles.costo)) / (ventas_detalles.cantidad * ventas_detalles.costo)) * 100, 2)
                ELSE 0 END as utilidad_porcentaje')
        )
            ->orderBy('s.nombre', 'asc')
            ->orderBy('p.nombreProducto', 'asc')
            ->get();

        $mapped = $rows->map(function ($d) {
            return [
                'sucursal'           => $d->sucursal,
                'facturador'         => $d->facturador,
                'codebar'            => $d->codebar,
                'nombreProducto'     => $d->nombreProducto,
                'cantidad'           => $d->cantidad,
                'costo'              => round($d->costo, 4),
                'costo_total'        => round($d->costo_total, 2),
                'precio'             => round($d->precio, 4),
                'total_venta'        => round($d->total_venta, 2),
                'utilidad_monto'     => round($d->utilidad_monto, 2),
                'utilidad_porcentaje'=> $d->utilidad_porcentaje,
            ];
        });

        $totalCosto  = $rows->sum('costo_total');
        $totalVentas = $rows->sum('total_venta');

        $mapped->push([
            'sucursal'           => '',
            'facturador'         => '',
            'codebar'            => '',
            'nombreProducto'     => 'TOTALES:',
            'cantidad'           => '',
            'costo'              => '',
            'costo_total'        => round($totalCosto, 2),
            'precio'             => '',
            'total_venta'        => round($totalVentas, 2),
            'utilidad_monto'     => round($totalVentas - $totalCosto, 2),
            'utilidad_porcentaje'=> $totalCosto > 0 ? round((($totalVentas - $totalCosto) / $totalCosto) * 100, 2) : 0,
        ]);

        return $mapped;
    }


    public function headings(): array
    {
        return ["Sucursal", "Facturador", "Codebar", "Producto", "Cantidad", "Costo U.", "Total Costo", "Precio U.", "Total Venta", "Utilidad", "% Utilidad"];
    }

    public function startCell(): string
    {
        return 'A2';
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A2:K2')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['argb' => 'FFFFFFFF'],
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => [
                    'argb' => '233446',
                ],
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => 'FFFFFFFF'],
                ],
            ],
        ]);

        // Formato moneda / porcentaje
        $sheet->getStyle('F')->getNumberFormat()->setFormatCode('"$"#,##0.0000');
        $sheet->getStyle('G')->getNumberFormat()->setFormatCode('"$"#,##0.00');
        $sheet->getStyle('H')->getNumberFormat()->setFormatCode('"$"#,##0.0000');
        $sheet->getStyle('I')->getNumberFormat()->setFormatCode('"$"#,##0.00');
        $sheet->getStyle('J')->getNumberFormat()->setFormatCode('"$"#,##0.00');
        $sheet->getStyle('K')->getNumberFormat()->setFormatCode('0.00"%"');

        // Ajustar el ancho de las columnas
        foreach (range('A', 'K') as $columnID) {
            $sheet->getColumnDimension($columnID)->setAutoSize(true);
        }

        // Aplicar bordes a todas las celdas de datos
        $lastRow = $sheet->getHighestRow();
        $sheet->getStyle('A3:K' . $lastRow)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => 'FF000000'],
                ],
            ],
        ]);

        // Centrar cantidad
        $sheet->getStyle('E3:E' . $lastRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        return [
            $lastRow => ['font' => ['bold' => true]],
        ];
    }

    public function title(): string
    {
        return 'Reporte de Utilidad';
    }
}
