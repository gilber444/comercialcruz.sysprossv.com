<?php

namespace App\Exports;

use App\Models\Arqueos;
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

class ReportArqueosExport implements FromCollection, WithHeadings, WithTitle, WithCustomStartCell, WithStyles
{
    protected $sucursal, $caja, $user, $dateTo, $dateFrom;

    function __construct($sucursal, $caja, $user, $f1, $f2)
    {
        $this->sucursal = $sucursal;
        $this->caja = $caja;
        $this->user =$user;
        $this->dateFrom = $f2;
        $this->dateTo = $f1;
    }

    public function collection()
    {

        $query = Arqueos::join('parametros as p', 'p.id', 'arqueos.caja')->join('users as u', 'u.id', 'arqueos.cajero')
        ->select(
            'arqueos.numero',
            DB::raw('DATE_FORMAT(arqueos.fecha, "%d/%m/%Y") as fecha'),
            'arqueos.hora',
            'p.caja',
            'u.name as cajero',
            'arqueos.tipo',
            'arqueos.efectivo',
            'arqueos.tarjeta',
            'arqueos.cheque',
            'arqueos.credito',
            'arqueos.subtotalPagos',
            'arqueos.devoluciones',
            'arqueos.anulaciones',
            'arqueos.percepcion',
            'arqueos.sumaTotales',
            'arqueos.ticketDesde',
            'arqueos.ticketHasta',
            'arqueos.gravadosT',
            'arqueos.ivaT',
            'arqueos.subT',
            'arqueos.totalT',
            'arqueos.consumidorDesde',
            'arqueos.consumidorHasta',
            'arqueos.gravadosCon',
            'arqueos.ivaCon',
            'arqueos.subCon',
            'arqueos.totalCon',
            'arqueos.CreDesde',
            'arqueos.CreHasta',
            'arqueos.gravadosCre',
            'arqueos.ivaCre',
            'arqueos.subCre',
            'arqueos.totalCre',
            'arqueos.dteDesde',
            'arqueos.dteHasta',
            'arqueos.gravadosDTE',
            'arqueos.ivaDTE',
            'arqueos.subDTE',
            'arqueos.totalDTE',
            'arqueos.creditosDesde',
            'creditosHasta',
            'gravadosCredi',
            'arqueos.ivaCredi',
            'arqueos.subCredi',
            'arqueos.totalCredi',
            'arqueos.totalGeneral',
            'arqueos.ivaGeneral',
            'arqueos.percepcion',
            'arqueos.subGeneral',
            'arqueos.totalPercepcion',
            'arqueos.totalGlobal',
            'arqueos.totalEfectivo',
            'arqueos.diferencia'
        );

        if ($this->sucursal != 0) {
            $query->where('arqueos.sucursal', $this->sucursal);
        }

        if ($this->caja != 0) {
            $query->where('p.id', $this->caja);
        }

        if ($this->user != 0) {
            $query->where('arqueos.cajero', $this->user);
        }

        if (!empty($this->dateTo) && !empty($this->dateFrom)) {
            $query->whereBetween('arqueos.fecha', [$this->dateTo, $this->dateFrom]);
        }

        return $query->get();
    }

    public function headings() : array
    {
        return ['Numero','Fecha',
        'Hora',
        'Caja',
        'Cajero',
        'Tipo',
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
        $sheet->getStyle('A2:AZ2')->applyFromArray([
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
        $sheet->getStyle('H')->getNumberFormat()->setFormatCode('"$"#,##0.00');
        $sheet->getStyle('I')->getNumberFormat()->setFormatCode('"$"#,##0.00');
        $sheet->getStyle('J')->getNumberFormat()->setFormatCode('"$"#,##0.00');
        $sheet->getStyle('K')->getNumberFormat()->setFormatCode('"$"#,##0.00');
        $sheet->getStyle('L')->getNumberFormat()->setFormatCode('"$"#,##0.00');
        $sheet->getStyle('M')->getNumberFormat()->setFormatCode('"$"#,##0.00');
        $sheet->getStyle('N')->getNumberFormat()->setFormatCode('"$"#,##0.00');
        $sheet->getStyle('O')->getNumberFormat()->setFormatCode('"$"#,##0.00');
        $sheet->getStyle('R')->getNumberFormat()->setFormatCode('"$"#,##0.00');
        $sheet->getStyle('S')->getNumberFormat()->setFormatCode('"$"#,##0.00');
        $sheet->getStyle('T')->getNumberFormat()->setFormatCode('"$"#,##0.00');
        $sheet->getStyle('U')->getNumberFormat()->setFormatCode('"$"#,##0.00');
        $sheet->getStyle('X')->getNumberFormat()->setFormatCode('"$"#,##0.00');
        $sheet->getStyle('Y')->getNumberFormat()->setFormatCode('"$"#,##0.00');
        $sheet->getStyle('Z')->getNumberFormat()->setFormatCode('"$"#,##0.00');
        $sheet->getStyle('AA')->getNumberFormat()->setFormatCode('"$"#,##0.00');
        $sheet->getStyle('AD')->getNumberFormat()->setFormatCode('"$"#,##0.00');
        $sheet->getStyle('AE')->getNumberFormat()->setFormatCode('"$"#,##0.00');
        $sheet->getStyle('AF')->getNumberFormat()->setFormatCode('"$"#,##0.00');
        $sheet->getStyle('AG')->getNumberFormat()->setFormatCode('"$"#,##0.00');
        $sheet->getStyle('AJ')->getNumberFormat()->setFormatCode('"$"#,##0.00');
        $sheet->getStyle('AK')->getNumberFormat()->setFormatCode('"$"#,##0.00');
        $sheet->getStyle('AL')->getNumberFormat()->setFormatCode('"$"#,##0.00');
        $sheet->getStyle('AM')->getNumberFormat()->setFormatCode('"$"#,##0.00');
        $sheet->getStyle('AP')->getNumberFormat()->setFormatCode('"$"#,##0.00');
        $sheet->getStyle('AQ')->getNumberFormat()->setFormatCode('"$"#,##0.00');
        $sheet->getStyle('AR')->getNumberFormat()->setFormatCode('"$"#,##0.00');
        $sheet->getStyle('AS')->getNumberFormat()->setFormatCode('"$"#,##0.00');
        $sheet->getStyle('AT')->getNumberFormat()->setFormatCode('"$"#,##0.00');
        $sheet->getStyle('AU')->getNumberFormat()->setFormatCode('"$"#,##0.00');
        $sheet->getStyle('AV')->getNumberFormat()->setFormatCode('"$"#,##0.00');
        $sheet->getStyle('AW')->getNumberFormat()->setFormatCode('"$"#,##0.00');
        $sheet->getStyle('AX')->getNumberFormat()->setFormatCode('"$"#,##0.00');
        $sheet->getStyle('AY')->getNumberFormat()->setFormatCode('"$"#,##0.00');
        $sheet->getStyle('AZ')->getNumberFormat()->setFormatCode('"$"#,##0.00');

        // Ajustar el ancho de las columnas
        foreach (range('A', 'AZ') as $columnID) {
            $sheet->getColumnDimension($columnID)->setAutoSize(true);
        }

        // Aplicar bordes a todas las celdas de datos
        $lastRow = $sheet->getHighestRow();
        $sheet->getStyle('A3:AZ' . $lastRow)->applyFromArray([
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
        $sheet->getStyle('F3:F' . $lastRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('P3:P' . $lastRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('Q3:Q' . $lastRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        $sheet->getStyle('V3:V' . $lastRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('W3:W' . $lastRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('AB3:AB' . $lastRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('AC3:AC' . $lastRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('AH3:AH' . $lastRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('AI3:AI' . $lastRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('AN3:AN' . $lastRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('AO3:AO' . $lastRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);


        // Aplicar formato de número a la columna de items
        $sheet->getStyle('A')->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle('P')->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle('Q')->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle('V')->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle('W')->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle('AB')->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle('AC')->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle('AH')->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle('AI')->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle('AN')->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle('AO')->getNumberFormat()->setFormatCode('#,##0');

        return [
            2 => ['font' => ['bold' => true]],
        ];
    }

    public function title(): string
    {
        return 'Reporte de Arqueos';
    }
}
