<?php

namespace App\Exports;

use App\Models\HojaInventarioDetalles;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class HojaExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    protected $hojaId;

    public function __construct($hojaId)
    {
        $this->hojaId = $hojaId;
    }

    public function collection()
    {
        return HojaInventarioDetalles::where('hoja', $this->hojaId)
            ->orderBy('nombre', 'asc')
            ->get();
    }

    public function headings(): array
    {
        return [
            'CÓDIGO',
            'DESCRIPCIÓN',
            'MEDIDA',
            'E. ANTERIOR',
            'CONTEO FÍSICO',
            'DIFERENCIA',
            'COSTO',
            'TOTAL',
        ];
    }

    public function map($row): array
    {
        return [
            $row->codebar,
            $row->nombre,
            DB::table('medidas')->where('id', $row->medida)->value('unidad') ?? '',
            $row->cantidadAnterior,
            $row->cantidadActual,
            $row->diferencia,
            $row->costo,
            $row->total,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill' => ['fillType' => 'solid', 'startColor' => ['argb' => 'FF233446']],
            ],
        ];
    }
}
