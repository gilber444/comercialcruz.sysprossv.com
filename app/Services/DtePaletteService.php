<?php

namespace App\Services;

use App\Models\PersonalizacionDte;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;

class DtePaletteService
{
    public const DEFAULTS = [
        'primario' => '#33475B',
        'encabezado' => '#EEF1F5',
        'texto' => '#1F2937',
        'texto_suave' => '#64748B',
        'borde' => '#DFE4EA',
        'exito' => '#1F7A43',
        'peligro' => '#B02A37',
        'fondo' => '#FFFFFF',
    ];

    public function obtener(?int $empresaId): array
    {
        if (!$empresaId || !Schema::hasTable('personalizaciones_dte')) {
            return self::DEFAULTS;
        }

        $registro = PersonalizacionDte::where('empresa_id', $empresaId)->first();

        if (!$registro) {
            return self::DEFAULTS;
        }

        return array_merge(self::DEFAULTS, $this->sanitizar([
            'primario' => $registro->color_primario,
            'encabezado' => $registro->color_encabezado,
            'texto' => $registro->color_texto,
            'texto_suave' => $registro->color_texto_suave,
            'borde' => $registro->color_borde,
            'exito' => $registro->color_exito,
            'peligro' => $registro->color_peligro,
            'fondo' => $registro->color_fondo,
        ], false));
    }

    public function guardar(int $empresaId, array $colores): array
    {
        $paleta = $this->sanitizar($colores, true);

        PersonalizacionDte::updateOrCreate(
            ['empresa_id' => $empresaId],
            [
                'color_primario' => $paleta['primario'],
                'color_encabezado' => $paleta['encabezado'],
                'color_texto' => $paleta['texto'],
                'color_texto_suave' => $paleta['texto_suave'],
                'color_borde' => $paleta['borde'],
                'color_exito' => $paleta['exito'],
                'color_peligro' => $paleta['peligro'],
                'color_fondo' => $paleta['fondo'],
            ]
        );

        return $paleta;
    }

    public function restablecer(int $empresaId): array
    {
        return $this->guardar($empresaId, self::DEFAULTS);
    }

    private function sanitizar(array $colores, bool $requerirTodos): array
    {
        $resultado = [];

        foreach (self::DEFAULTS as $clave => $predeterminado) {
            if (!array_key_exists($clave, $colores)) {
                if ($requerirTodos) {
                    throw new InvalidArgumentException("Falta el color {$clave}.");
                }

                continue;
            }

            $color = strtoupper(trim((string) $colores[$clave]));

            if (!preg_match('/^#[0-9A-F]{6}$/', $color)) {
                if ($requerirTodos) {
                    throw new InvalidArgumentException("El color {$clave} no es valido.");
                }

                continue;
            }

            $resultado[$clave] = $color;
        }

        return $resultado;
    }
}
