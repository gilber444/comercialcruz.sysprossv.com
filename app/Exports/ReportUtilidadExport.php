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

        $data = $query->select(
            's.nombre as sucursal',
            'f.facturador as facturador',
            'p.nombreProducto',
            'ventas_detalles.cantidad',
            'ventas_detalles.costo as costo',
            'ventas_detalles.costo_total',
            'ventas_detalles.precio as precio',
            'ventas_detalles.total as total_venta',
            'ventas_detalles.utilidad_uni as utilidad_uni',
            'ventas_detalles.utilidad as total_utilidad'
        )
            //->groupBy('s.nombre', 'f.facturador', 'p.nombreProducto')
            ->orderBy('s.nombre', 'asc')
            ->get();

        // Puedes agregar una fila total si lo deseas
        $total = [
            'sucursal'        => '',
            'facturador'      => '',
            'nombreProducto'  => 'TOTALES:',
            'cantidad'        => '',
            'costo'           => '',
            'total_costo'     => ($data->sum('costo_total') * 1.13),
            'precio'          => '',
            'total_venta'     => $data->sum('total_venta'),
            'utilidad_uni'    => '',
            'total_utilidad'  => ($data->sum('total_venta') - ($data->sum('costo_total') * 1.13)),
        ];

        $data->push((object) $total);

        return $data;
    }


    public function headings(): array
    {
        return ["Codebar", "Producto", "Fecha", "Cantidad", "Costo", "Total Costo", "Precio", "Total Precio", "Utilidad U.", "Utilidad T."];
    }

    public function startCell(): string
    {
        return 'A2';
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A2:J2')->applyFromArray([
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
        $sheet->getStyle('E')->getNumberFormat()->setFormatCode('"$"#,##0.0000');
        $sheet->getStyle('F')->getNumberFormat()->setFormatCode('"$"#,##0.0000');
        $sheet->getStyle('G')->getNumberFormat()->setFormatCode('"$"#,##0.0000');
        $sheet->getStyle('H')->getNumberFormat()->setFormatCode('"$"#,##0.0000');
        $sheet->getStyle('I')->getNumberFormat()->setFormatCode('"$"#,##0.0000');
        $sheet->getStyle('J')->getNumberFormat()->setFormatCode('"$"#,##0.0000');

        // Ajustar el ancho de las columnas
        foreach (range('A', 'J') as $columnID) {
            $sheet->getColumnDimension($columnID)->setAutoSize(true);
        }

        // Aplicar bordes a todas las celdas de datos
        $lastRow = $sheet->getHighestRow();
        $sheet->getStyle('A3:J' . $lastRow)->applyFromArray([
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

        return [
            4 => ['font' => ['bold' => true]],
        ];
    }

    public function title(): string
    {
        return 'Reporte de Utilidad';
    }
}
