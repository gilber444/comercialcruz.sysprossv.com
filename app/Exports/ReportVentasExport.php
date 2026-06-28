<?php

namespace App\Exports;

use App\Models\Ventas;
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

class ReportVentasExport implements FromCollection, WithHeadings, WithTitle, WithCustomStartCell, WithStyles
{
    protected $sucursal, $reportType, $dateFrom, $dateTo;

    function __construct($sucursal, $reportType, $f1, $f2)
    {
        $this->sucursal = $sucursal;
        $this->reportType = $reportType;
        $this->dateFrom = $f1;
        $this->dateTo = $f2;
    }

    public function collection()
    {
        $data = [];

        if($this->reportType == 1)
        {
            $from = Carbon::parse($this->dateFrom)->format('Y-m-d');
            $to = Carbon::parse($this->dateTo)->format('Y-m-d');
            $today = date('Y-m-d');
        }
        else
        {
            $from = Carbon::parse($this->dateFrom)->format('Y-m-d');
            $to = Carbon::parse($this->dateTo)->format('Y-m-d');
            $today = date('Y-m-d');
        }

        if($this->sucursal == 0)
        {
            $data = Ventas::join('sucursales as s', 's.id', 'ventas.sucursal')
            ->join('clientes as c', 'c.id', 'ventas.cliente')
            ->join('facturadores as f', 'f.id', 'ventas.facturador')
            ->select('ventas.numero', 'ventas.codigo',  DB::raw('DATE_FORMAT(ventas.fecha, "%d/%m/%Y") as fecha'), 'f.facturador as facturadors', 'c.nombreCliente', DB::raw('(SELECT SUM(vd.cantidad) FROM ventas_detalles as vd WHERE vd.venta = ventas.id) as items'), DB::raw('FORMAT(ventas.total, 2) as total'))
            ->whereBetween('ventas.fecha', [$from, $to])
            ->orderBy('ventas.fecha', 'asc')
            ->get();
        }
        else
        {
            $data = Ventas::join('sucursales as s', 's.id', 'ventas.sucursal')
            ->join('clientes as c', 'c.id', 'ventas.cliente')
            ->join('facturadores as f', 'f.id', 'ventas.facturador')
            ->select('ventas.numero', 'ventas.codigo',  DB::raw('DATE_FORMAT(ventas.fecha, "%d/%m/%Y") as fecha'), 'f.facturador as facturadors', 'c.nombreCliente', DB::raw('(SELECT SUM(vd.cantidad) FROM ventas_detalles as vd WHERE vd.venta = ventas.id) as items'), DB::raw('FORMAT(ventas.total, 2) as total'))
            ->whereBetween('ventas.fecha', [$from, $to])
            ->where('ventas.sucursal', $this->sucursal)
            ->orderBy('ventas.fecha', 'asc')
            ->get();
        }
        return $data;
    }

    public function headings() : array
    {
        return ["Número Control", "Codigo Generación", "Fecha", "Facturador", "Cliente", "Items", "Total"];
    }

    public function startCell() : string
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

        // Formatear la columna de Total
        $sheet->getStyle('G')->getNumberFormat()->setFormatCode('"$"#,##0.00');

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
        $sheet->getStyle('C3:C' . $lastRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('F3:F' . $lastRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);


        // Aplicar formato de número a la columna de items
        $sheet->getStyle('F')->getNumberFormat()->setFormatCode('#,##0');

        return [
            2 => ['font' => ['bold' => true]],
        ];
    }

    public function title(): string
    {
        return 'Reporte de Ventas';
    }
}
