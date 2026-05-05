<?php

namespace App\Exports;

use App\Models\Cortes;
use Maatwebsite\Excel\Concerns\FromCollection;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class ReportCortesZExport implements FromCollection, WithHeadings, WithTitle, WithCustomStartCell, WithStyles
{
    protected $sucursal, $caja, $dateTo, $dateFrom;

    function __construct($sucursal, $caja, $f1, $f2)
    {
        $this->sucursal = $sucursal;
        $this->caja = $caja;
        $this->dateFrom = $f2;
        $this->dateTo = $f1;
    }

    public function collection()
    {
        $query = Cortes::join('parametros as p', 'p.id', 'cortes.caja')
        ->select(
            'cortes.corte',
            'cortes.fecha',
            'cortes.hora',
            'p.caja',
            'cortes.efectivo',
            'cortes.tarjeta',
            'cortes.cheque',
            'cortes.credito',
            'cortes.subtotalPagos',
            'cortes.devoluciones',
            'cortes.anulaciones',
            'cortes.percepcion',
            'cortes.sumaTotales',
            'cortes.ticketDesde',
            'cortes.ticketHasta',
            'cortes.gravadosT',
            'cortes.ivaT',
            'cortes.subT',
            'cortes.totalT',
            'cortes.consumidorDesde',
            'cortes.consumidorHasta',
            'cortes.gravadosCon',
            'cortes.ivaCon',
            'cortes.subCon',
            'totalCon',
            'cortes.CreDesde',
            'cortes.CreHasta',
            'cortes.gravadosCre',
            'cortes.ivaCre',
            'cortes.subCre',
            'cortes.totalCre',
            'cortes.dteDesde',
            'cortes.dteHasta',
            'cortes.gravadosDTE',
            'cortes.ivaDTE',
            'cortes.subDTE',
            'cortes.totalDTE',
            'cortes.creditosDesde',
            'cortes.creditosHasta',
            'cortes.gravadosCredi',
            'cortes.ivaCredi',
            'cortes.subCredi',
            'cortes.totalCredi',
            'cortes.totalGeneral',
            'cortes.ivaGeneral',
            'cortes.subGeneral',
            'cortes.totalPercepcion',
            'cortes.totalGlobal',
            'cortes.totalEfectivo',
            'cortes.diferencia'
        );

        $query->where('cortes.estado', 'Cerrado');
        
        if ($this->sucursal != 0) {
            $query->where('cortes.sucursal', $this->sucursal);
        }

        if ($this->caja != 0) {
            $query->where('p.id', $this->caja);
        }

        if (!empty($this->dateTo) && !empty($this->dateFrom)) {
            $query->whereBetween('cortes.fecha', [$this->dateTo, $this->dateFrom]);
        }
        return $query->get();
    }

    public function headings() : array
    {
        return ['Numero','Fecha',
        'Hora',
        'Caja',
        'Efectivo',
        'Tarjeta',
        'Cheque',
        'Credito',
        'SubtotalPagos',
        'Devoluciones',
        'Anulaciones',
        'Percepcion',
        'SumaTotales',
        'TicketDesde',
        'TicketHasta',
        'GravadosT',
        'IVA',
        'SubT',
        'Total',
        'ConsumidorDesde',
        'ConsumidorHasta',
        'Gravados',
        'IVA',
        'Sub',
        'Total',
        'CreditoDesde',
        'CreditoHasta',
        'Gravados',
        'IVA',
        'Sub',
        'Total',
        'DTE Desde',
        'DTE Hasta',
        'Gravados',
        'IVA',
        'sub',
        'Total',
        'CreditosDesde',
        'CreditosHasta',
        'Gravados',
        'IVA',
        'Sub',
        'Total',
        'Total General',
        'IVA General',
        'SubGeneral',
        'TotalPercepcion',
        'TotalGlobal',
        'TotalEfectivo',
        'Diferencia'];
    }

    public function startCell() : string
    {
        return 'A2';
    }

     public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A2:AX2')->applyFromArray([
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
        $sheet->getStyle('F')->getNumberFormat()->setFormatCode('"$"#,##0.00');
        $sheet->getStyle('G')->getNumberFormat()->setFormatCode('"$"#,##0.00');
        $sheet->getStyle('H')->getNumberFormat()->setFormatCode('"$"#,##0.00');
        $sheet->getStyle('I')->getNumberFormat()->setFormatCode('"$"#,##0.00');
        $sheet->getStyle('L')->getNumberFormat()->setFormatCode('"$"#,##0.00');
        $sheet->getStyle('M')->getNumberFormat()->setFormatCode('"$"#,##0.00');
        $sheet->getStyle('P')->getNumberFormat()->setFormatCode('"$"#,##0.00');
        $sheet->getStyle('Q')->getNumberFormat()->setFormatCode('"$"#,##0.00');
        $sheet->getStyle('R')->getNumberFormat()->setFormatCode('"$"#,##0.00');
        $sheet->getStyle('S')->getNumberFormat()->setFormatCode('"$"#,##0.00');
        $sheet->getStyle('V')->getNumberFormat()->setFormatCode('"$"#,##0.00');
        $sheet->getStyle('W')->getNumberFormat()->setFormatCode('"$"#,##0.00');
        $sheet->getStyle('X')->getNumberFormat()->setFormatCode('"$"#,##0.00');
        $sheet->getStyle('Y')->getNumberFormat()->setFormatCode('"$"#,##0.00');
        $sheet->getStyle('AB')->getNumberFormat()->setFormatCode('"$"#,##0.00');
        $sheet->getStyle('AC')->getNumberFormat()->setFormatCode('"$"#,##0.00');
        $sheet->getStyle('AD')->getNumberFormat()->setFormatCode('"$"#,##0.00');
        $sheet->getStyle('AE')->getNumberFormat()->setFormatCode('"$"#,##0.00');
        $sheet->getStyle('AH')->getNumberFormat()->setFormatCode('"$"#,##0.00');
        $sheet->getStyle('AI')->getNumberFormat()->setFormatCode('"$"#,##0.00');
        $sheet->getStyle('AJ')->getNumberFormat()->setFormatCode('"$"#,##0.00');
        $sheet->getStyle('AK')->getNumberFormat()->setFormatCode('"$"#,##0.00');
        $sheet->getStyle('AN')->getNumberFormat()->setFormatCode('"$"#,##0.00');
        $sheet->getStyle('AO')->getNumberFormat()->setFormatCode('"$"#,##0.00');
        $sheet->getStyle('AP')->getNumberFormat()->setFormatCode('"$"#,##0.00');
        $sheet->getStyle('AQ')->getNumberFormat()->setFormatCode('"$"#,##0.00');
        $sheet->getStyle('AR')->getNumberFormat()->setFormatCode('"$"#,##0.00');
        $sheet->getStyle('AS')->getNumberFormat()->setFormatCode('"$"#,##0.00');
        $sheet->getStyle('AT')->getNumberFormat()->setFormatCode('"$"#,##0.00');
        $sheet->getStyle('AU')->getNumberFormat()->setFormatCode('"$"#,##0.00');
        $sheet->getStyle('AV')->getNumberFormat()->setFormatCode('"$"#,##0.00');
        $sheet->getStyle('AW')->getNumberFormat()->setFormatCode('"$"#,##0.00');
        $sheet->getStyle('AX')->getNumberFormat()->setFormatCode('"$"#,##0.00');

        // Ajustar el ancho de las columnas
        foreach (range('A', 'AX') as $columnID) {
            $sheet->getColumnDimension($columnID)->setAutoSize(true);
        }

        // Aplicar bordes a todas las celdas de datos
        $lastRow = $sheet->getHighestRow();
        $sheet->getStyle('A3:AX' . $lastRow)->applyFromArray([
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
        $sheet->getStyle('C3:C' . $lastRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('D3:D' . $lastRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('N3:N' . $lastRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('O3:O' . $lastRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('T3:T' . $lastRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        $sheet->getStyle('U3:U' . $lastRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('Z3:Z' . $lastRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('AA3:AA' . $lastRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('AC3:AC' . $lastRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('AF3:AF' . $lastRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('AG3:AG' . $lastRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('AL3:AL' . $lastRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('AM3:AM' . $lastRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);


        // Aplicar formato de número a la columna de items
        $sheet->getStyle('D')->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle('N')->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle('O')->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle('T')->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle('U')->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle('Z')->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle('AA')->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle('AF')->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle('AG')->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle('AL')->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle('AM')->getNumberFormat()->setFormatCode('#,##0');

        return [
            2 => ['font' => ['bold' => true]],
        ];
    }

    public function title(): string
    {
        return 'Reporte de Cortes Z';
    }
}
