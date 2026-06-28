<?php

namespace App\Exports;

use App\Models\Ventas;
use App\Models\VentasDetalles;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class ReportUtilidadSinExport implements FromCollection, WithHeadings, WithTitle, WithCustomStartCell, WithStyles
{
    protected $sucursal, $caja, $reportType, $dateFrom, $dateTo;

    function __construct($sucursal, $caja, $reportType, $f1 = null, $f2 = null)
    {
        $this->sucursal = $sucursal;
        $this->caja = $caja;
        $this->reportType = $reportType;
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

        $base = VentasDetalles::query()
            ->join('ventas', 'ventas.id', '=', 'ventas_detalles.venta')
            ->join('sucursales as s', 's.id', '=', 'ventas.sucursal')
            /*->join('precios', function ($join) {
                $join->on('precios.producto', '=', 'ventas_detalles.producto')
                    ->where('precios.cantidad', '=', 1);
            })*/
            ->whereBetween('ventas.fecha', [$from, $to])
            ->where('ventas.estado', 'Cancelado');

        if ($this->sucursal != 0) $base->where('ventas.sucursal', $this->sucursal);
        if ($this->caja != 0)     $base->where('ventas.caja', $this->caja);

        $groupCols = [];
        if ($this->caja != 0) {
            $groupCols = ['ventas.caja'];
        } elseif ($this->sucursal != 0) {
            $groupCols = ['ventas.caja'];
        } else {
            $groupCols = ['ventas.sucursal', 'ventas.caja'];
        }


        $data = (clone $base)
            ->selectRaw('
                s.nombre as nombre_sucursal,
                ventas.caja,
                SUM(ventas_detalles.total) as total_venta,
                SUM(ventas_detalles.costo * ventas_detalles.descargar) as total_costo
            ')
            ->groupBy('s.nombre', 'ventas.sucursal', 'ventas.caja')
            ->orderBy('s.nombre')
            ->orderBy('ventas.caja')
            ->get();

        $dateLabel = "{$from} al {$to}";

        $mapped = $data->map(function ($row) use ($dateLabel) {
            return [
                'Fecha'        => $dateLabel,
                'Sucursal'     => $row->nombre_sucursal,
                'Caja'         => $row->caja,
                'Total Ventas' => round($row->total_venta, 2),
                'Total Costo'  => round($row->total_costo, 2),
                'Utilidad'     => round(($row->total_venta - $row->total_costo), 2),
                '%Utilidad'     => $row->total_costo > 0 ? round((($row->total_venta - $row->total_costo) / $row->total_costo) * 100, 2) : 0,
            ];
        });

        // Fila de totales
        $mapped->push([
            'Fecha'        => '',
            'Sucursal'     => '',
            'Caja'         => 'TOTALES:',
            'Total Ventas' => round($data->sum('total_venta'), 2),
            'Total Costo'  => round($data->sum('total_costo'), 2),
            'Utilidad'     => round($data->sum('total_venta') - $data->sum('total_costo'), 2),
            '%Utilidad'     => $data->sum('total_costo') > 0
                ? round((($data->sum('total_venta') - $data->sum('total_costo')) / $data->sum('total_costo')) * 100, 2)
                : 0,
        ]);

        return $mapped;
    }


    public function headings(): array
    {
        return ["Fecha", "Sucursal", "Caja", "Total Ventas", "Total Costo", "Utilidad", " % Utilidad"];
    }

    public function startCell(): string
    {
        return 'A2';
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A2:G2')->applyFromArray([
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

        // Formatear la columna de a 4 decimales
        $sheet->getStyle('D')->getNumberFormat()->setFormatCode('"$"#,##0.00');
        $sheet->getStyle('E')->getNumberFormat()->setFormatCode('"$"#,##0.00');
        $sheet->getStyle('F')->getNumberFormat()->setFormatCode('"$"#,##0.00');
        $sheet->getStyle('G')->getNumberFormat()->setFormatCode('0.00%');
        //$sheet->getStyle('H')->getNumberFormat()->setFormatCode('"$"#,##0.000');

        // Ajustar el ancho de las columnas
        foreach (range('A', 'G') as $columnID) {
            $sheet->getColumnDimension($columnID)->setAutoSize(true);
        }

        // Aplicar bordes a todas las celdas de datos
        $lastRow = $sheet->getHighestRow();
        $sheet->getStyle('A3:G' . $lastRow)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => 'FF000000'],
                ],
            ],
        ]);

        // Centrar la columna de fecha y items
        //$sheet->getStyle('A3:C' . $lastRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        //$sheet->getStyle('B3:F' . $lastRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        //$sheet->getStyle('C3:F' . $lastRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        //return [
        //4 => ['font' => ['bold' => true]],
        //];
    }

    public function title(): string
    {
        return 'Reporte de Utilidad Sintetico';
    }
}