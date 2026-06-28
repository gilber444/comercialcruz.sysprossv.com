<?php

namespace App\Exports;

use App\Models\AperturaInventario;
use App\Models\HojaInventario;
use App\Models\HojaInventarioDetalles;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class NoContadosExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    protected $aperturaId;

    public function __construct($aperturaId)
    {
        $this->aperturaId = $aperturaId;
    }

    public function collection()
    {
        $apertura = AperturaInventario::findOrFail($this->aperturaId);

        $hojaIds = HojaInventario::where('apertura_id', $apertura->id)->pluck('id');

        $productosContados = HojaInventarioDetalles::whereIn('hoja', $hojaIds)
            ->distinct()
            ->pluck('producto')
            ->toArray();

        return DB::table('inventarios as inv')
            ->join('productos as p', 'inv.producto', '=', 'p.id')
            ->where('inv.sucursal', $apertura->sucursal)
            ->when(!empty($productosContados), fn($q) => $q->whereNotIn('inv.producto', $productosContados))
            ->select('p.nombreProducto as nombre', 'p.codebar3 as codebar', 'inv.existencia')
            ->orderBy('p.nombreProducto')
            ->get();
    }

    public function headings(): array
    {
        return ['CÓDIGO', 'DESCRIPCIÓN', 'EXISTENCIA SISTEMA'];
    }

    public function map($row): array
    {
        return [
            $row->codebar ?? '',
            $row->nombre,
            $row->existencia,
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
