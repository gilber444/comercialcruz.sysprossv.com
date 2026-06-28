<?php

namespace App\Exports;

use App\Models\HojaInventario;
use App\Models\HojaInventarioDetalles;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class HojaAperturaExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    protected $aperturaId;

    public function __construct($aperturaId)
    {
        $this->aperturaId = $aperturaId;
    }

    public function collection()
    {
        $hojaIds = HojaInventario::where('apertura_id', $this->aperturaId)->pluck('id');

        $first = HojaInventarioDetalles::whereIn('hoja', $hojaIds)
            ->selectRaw('producto, MIN(id) as first_id')
            ->groupBy('producto');

        $sumas = HojaInventarioDetalles::whereIn('hoja', $hojaIds)
            ->selectRaw('producto,
                SUM(cantidadActual) as cont_fisico,
                AVG(costo) as costo_prom,
                MAX(medida) as medida_show,
                MAX(codebar) as codebar_show,
                MAX(nombre) as nombre_show
            ')
            ->groupBy('producto');

        return HojaInventarioDetalles::query()
            ->joinSub($first, 'f', function ($join) {
                $join->on('hoja_inventario_detalles.producto', '=', 'f.producto')
                    ->on('hoja_inventario_detalles.id', '=', 'f.first_id');
            })
            ->joinSub($sumas, 's', function ($join) {
                $join->on('hoja_inventario_detalles.producto', '=', 's.producto');
            })
            ->selectRaw('
                s.codebar_show as codebar,
                s.nombre_show as nombre,
                s.medida_show as medida,
                hoja_inventario_detalles.cantidadAnterior as cantidadAnterior,
                s.cont_fisico as cantidadActual,
                (s.cont_fisico - hoja_inventario_detalles.cantidadAnterior) as diferencia,
                s.costo_prom as costo,
                (s.cont_fisico * s.costo_prom) as total,
                (hoja_inventario_detalles.cantidadAnterior * s.costo_prom) as totalAnterior,
                (s.cont_fisico * s.costo_prom) - (hoja_inventario_detalles.cantidadAnterior * s.costo_prom) as totalDiferencia
            ')
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
            'TOTAL CONTEO',
            'TOTAL ANTERIOR',
            'TOTAL DIFERENCIA',
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
            number_format($row->costo, 4),
            number_format($row->total, 2),
            number_format($row->totalAnterior, 2),
            number_format($row->totalDiferencia, 2),
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
