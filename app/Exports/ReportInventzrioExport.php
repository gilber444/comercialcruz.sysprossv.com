<?php

namespace App\Exports;

use App\Models\Inventarios;
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

class ReportInventzrioExport implements FromCollection, WithHeadings, WithTitle, WithCustomStartCell, WithStyles
{
    protected $sucursal;

    function __construct($sucursal)
    {
        $this->sucursal = $sucursal;
    }

    public function collection()
    {
        $data = [];

        if($this->sucursal == 0)
        {
            $data = Inventarios::join('productos as p', 'p.id', 'inventarios.producto')
            ->join('categorias as c', 'c.id', 'p.categoria')
            ->join('medidas as m', 'm.id', 'p.medida')
            ->select('p.codebar3', 'p.nombreProducto', 'c.categoria', 'm.unidad as medida', DB::raw('SUM(inventarios.existencia) as existencia'))
            ->groupBy('p.codebar3', 'p.nombreProducto', 'c.categoria', 'm.unidad')
            ->get();
        }
        else
        {
            $data = Inventarios::join('productos as p', 'p.id', 'inventarios.producto')
            ->join('sucursales as s', 's.id', 'inventarios.sucursal')
            ->join('categorias as c', 'c.id', 'p.categoria')
            ->join('medidas as m', 'm.id', 'p.medida')
            ->select('p.codebar3', 'p.nombreProducto', 'c.categoria', 'm.unidad as medida', 'inventarios.existencia')
            ->where('inventarios.sucursal', $this->sucursal)
            ->get();
        }

        return $data;
    }

    public function headings() : array
    {
        return ["Codigo de Barra", "Categoria", "Descripcion", "Medida", "Sucursal", "Existencia"];
    }

    public function startCell() : string
    {
        return 'A2';
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A2:F2')->applyFromArray([
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
        //$sheet->getStyle('G')->getNumberFormat()->setFormatCode('"$"#,##0.00');

        // Ajustar el ancho de las columnas
        foreach (range('A', 'F') as $columnID) {
            $sheet->getColumnDimension($columnID)->setAutoSize(true);
        }

        // Aplicar bordes a todas las celdas de datos
        $lastRow = $sheet->getHighestRow();
        $sheet->getStyle('A3:F' . $lastRow)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => 'FF000000'],
                ],
            ],
        ]);

        // Centrar la columna de fecha y items
        $sheet->getStyle('A3:A' . $lastRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('B3:B' . $lastRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('D3:D' . $lastRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('E3:E' . $lastRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);


        // Aplicar formato de número a la columna de items
        $sheet->getStyle('F')->getNumberFormat()->setFormatCode('#,##0');

        return [
            2 => ['font' => ['bold' => true]],
        ];
    }

    public function title(): string
    {
        return 'Reporte de Inventario';
    }

}
