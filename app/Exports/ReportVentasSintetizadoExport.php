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

class ReportVentasSintetizadoExport implements FromCollection, WithHeadings, WithTitle, WithCustomStartCell, WithStyles
{
    protected $sucursal, $reportType, $facturador, $dateFrom, $dateTo;

    function __construct($sucursal, $reportType, $facturador, $f1, $f2)
    { 
        $this->sucursal = $sucursal;
        $this->reportType = $reportType;
        $this->facturador = $facturador;
        $this->dateFrom = $f1;
        $this->dateTo = $f2;
    }

    public function collection()
    {
        $data = [];

        if ($this->reportType == 1) {
            $from = Carbon::parse($this->dateFrom)->format('Y-m-d');
            $to = Carbon::parse($this->dateTo)->format('Y-m-d');
            $today = date('Y-m-d');
        } else {
            $from = Carbon::parse($this->dateFrom)->format('Y-m-d');
            $to = Carbon::parse($this->dateTo)->format('Y-m-d');
            $today = date('Y-m-d');
        }

        $query = Ventas::join('sucursales as s', 's.id', 'ventas.sucursal')
            ->select(
                's.nombre as sucursal',
                \DB::raw("'" . $from . "' as desde"),
                \DB::raw("'" . $to . "' as hasta"),
                \DB::raw('COUNT(ventas.id) as total_ventas'),
                \DB::raw('SUM(ventas.total) as total_monto')
            )
            ->whereBetween('ventas.fecha', [$from, $to])
            ->where('ventas.estado', 'cancelado')
            ->groupBy('s.nombre')
            ->orderBy('s.nombre');

        if ($this->sucursal != 0) {
            $query->where('ventas.sucursal', $this->sucursal);
        }

        if ($this->facturador != 0) {
            $query->where('ventas.facturador', $this->facturador);
        }

        $data = $query->get();

        // ➤ SUMA FINAL
        $totalVentas = $data->sum('total_ventas');
        $totalMonto = $data->sum('total_monto');

        // ➤ AGREGAR FILA FINAL
        $data->push([
            'sucursal' => 'TOTAL GENERAL',
            'desde' => '',
            'hasta' => '',
            'total_ventas' => $totalVentas,
            'total_monto' => $totalMonto
        ]);

        return $data;
    }

    public function headings(): array
    {
        return ["Sucursal", "Desde", "Hasta", "Cantidad Ventas", "Total Ventas"];
    }

    public function startCell(): string
    {
        return 'A2';
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A2:E2')->applyFromArray([
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
        $sheet->getStyle('E')->getNumberFormat()->setFormatCode('"$"#,##0.00');

        // Ajustar el ancho de las columnas
        foreach (range('A', 'E') as $columnID) {
            $sheet->getColumnDimension($columnID)->setAutoSize(true);
        }

        // Aplicar bordes a todas las celdas de datos
        $lastRow = $sheet->getHighestRow();

        // Estilo para la fila TOTAL GENERAL
        $sheet->getStyle('A' . $lastRow . ':E' . $lastRow)->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['argb' => 'FF000000'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FFDADADA'],
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => 'FF000000'],
                ],
            ],
        ]);


        // Centrar la columna de fecha y items
        $sheet->getStyle('B3:B' . $lastRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('C3:C' . $lastRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        return [
            2 => ['font' => ['bold' => true]],
        ];
    }

    public function title(): string
    {
        return 'Reporte de Ventas Sintetizado';
    }
}