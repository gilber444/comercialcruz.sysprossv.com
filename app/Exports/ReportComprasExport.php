<?php

namespace App\Exports;

use App\Models\Compras;
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

class ReportComprasExport implements FromCollection, WithHeadings, WithTitle, WithCustomStartCell, WithStyles
{
    protected $proveedor, $reportType, $dateFrom, $dateTo;

    function __construct($proveedor, $reportType, $f1, $f2)
    {
        $this->proveedor   = $proveedor;
        $this->reportType  = $reportType;
        $this->dateFrom    = $f1;
        $this->dateTo      = $f2;
    }

    public function collection()
    {
        if ($this->reportType == 0) {
            $from = Carbon::now()->format('Y-m-d');
            $to   = Carbon::now()->format('Y-m-d');
        } else {
            $from = Carbon::parse($this->dateFrom)->format('Y-m-d');
            $to   = Carbon::parse($this->dateTo)->format('Y-m-d');
        }

        $query = Compras::join('proveedores as p', 'p.id', '=', 'compras.proveedor')
            ->join('tipo_compras as tc', 'tc.id', '=', 'compras.tipo')
            ->select(
                'compras.correlativo',
                'compras.serie',
                DB::raw("DATE_FORMAT(compras.fecha, '%d/%m/%Y') as fecha"),
                'tc.tipo as facturadors',
                'p.nombre',
                DB::raw('(SELECT SUM(cd.cantidad) FROM compras_detalles as cd WHERE cd.compra = compras.id) as items'),
                DB::raw('(SELECT SUM(cd.total)/1.13 FROM compras_detalles as cd WHERE cd.compra = compras.id) as subtotal'),
                DB::raw('(SELECT SUM(cd.total) - SUM(cd.total)/1.13 FROM compras_detalles as cd WHERE cd.compra = compras.id) as iva'),
                DB::raw('0 as percepcion'),
                DB::raw('(SELECT SUM(cd.total) FROM compras_detalles as cd WHERE cd.compra = compras.id) as total')
            )
            ->whereBetween('compras.fecha', [$from, $to]);

        if ($this->proveedor != 0) {
            $query->where('compras.proveedor', $this->proveedor);
        }

        $data = $query->get();

        $totalSubtotal   = $data->sum('subtotal');
        $totalIva        = $data->sum('iva');
        $totalPercepcion = 0;
        $totalTotal      = $data->sum('total');

        $data->push([
            'correlativo' => '',
            'serie'       => '',
            'fecha'       => '',
            'facturadors' => '',
            'nombre'      => 'TOTALES',
            'items'       => '',
            'subtotal'    => $totalSubtotal,
            'iva'         => $totalIva,
            'percepcion'  => $totalPercepcion,
            'total'       => $totalTotal,
        ]);

        return $data;
    }

    public function headings(): array
    {
        return ['Número Control', 'Código Generación', 'Fecha', 'Facturador', 'Proveedor', 'Items', 'SubTotal', 'IVA', 'Percepción', 'Total'];
    }

    public function startCell(): string
    {
        return 'A2';
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A2:J2')->applyFromArray([
            'font' => [
                'bold'  => true,
                'color' => ['argb' => 'FFFFFFFF'],
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
            ],
            'fill' => [
                'fillType'   => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FF233446'],
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color'       => ['argb' => 'FFFFFFFF'],
                ],
            ],
        ]);

        foreach (['G', 'H', 'I', 'J'] as $col) {
            $sheet->getStyle($col)->getNumberFormat()->setFormatCode('"$"#,##0.00');
        }
        $sheet->getStyle('F')->getNumberFormat()->setFormatCode('#,##0');

        foreach (range('A', 'J') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $lastRow = $sheet->getHighestRow();
        $sheet->getStyle('A3:J' . $lastRow)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color'       => ['argb' => 'FF000000'],
                ],
            ],
        ]);

        $sheet->getStyle('C3:C' . $lastRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('F3:F' . $lastRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        // Fila de totales en oscuro
        $sheet->getStyle('A' . $lastRow . ':J' . $lastRow)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF233446']],
        ]);

        return [2 => ['font' => ['bold' => true]]];
    }

    public function title(): string
    {
        return 'Reporte de Compras';
    }
}
