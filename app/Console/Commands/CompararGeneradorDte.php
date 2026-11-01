<?php

namespace App\Console\Commands;

use App\Services\GeneradorDteV2Service;
use App\Services\ValidadorEsquemaDte;
use App\Support\EsquemaDteParser;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Opis\JsonSchema\Errors\ErrorFormatter;
use Opis\JsonSchema\SchemaLoader;
use Opis\JsonSchema\Validator;

/**
 * Compara, SIN GUARDAR NADA, el JSON que arma hoy el sistema con el de la
 * Normativa 2.0 para uno o varios DTE. Todo corre dentro de una transaccion que
 * se revierte, porque los generadores actuales escriben su jsonDte.
 */
class CompararGeneradorDte extends Command
{
    protected $signature = 'dte:comparar-generador
        {ids* : Id(s) del DTE (tabla dtes)}
        {--esquemas= : Carpeta con los esquemas oficiales (por defecto resources/dte/esquemas)}';

    protected $description = 'Compara el JSON actual con el de la Normativa 2.0 (no guarda nada)';

    /** Archivo oficial por tipo, para --esquemas. */
    private const ARCHIVOS = [
        '01' => 'fe-f-v2.json',
        '03' => 'fe-ccf-v4.json',
        '05' => 'fe-nc-v4.json',
        '14' => 'fe-fse-v2.json',
    ];

    /** Montos del resumen a comparar: campo 2.0 => campo actual. */
    private const MONTOS = [
        'totalNoSuj' => 'totalNoSuj',
        'totalExenta' => 'totalExenta',
        'totalGravada' => 'totalGravada',
        'subTotalVentas' => 'subTotalVentas',
        'totalDescu' => 'totalDescu',
        'subTotal' => 'subTotal',
        'totalIva' => 'totalIva',
        'ivaPerci' => 'ivaPerci1',
        'ivaRete' => 'ivaRete1',
        'reteRenta' => 'reteRenta',
        'montoTotalOperacion' => 'montoTotalOperacion',
        'totalCompra' => 'totalCompra',
        'totalPagar' => 'totalPagar',
    ];

    public function handle(GeneradorDteV2Service $generador): int
    {
        $carpeta = storage_path('app/comparacion-dte');
        if (!is_dir($carpeta)) {
            mkdir($carpeta, 0775, true);
        }

        foreach ($this->argument('ids') as $id) {
            $this->newLine();
            $this->info("══════════ DTE {$id} ══════════");

            try {
                DB::beginTransaction();
                $r = $generador->construir((int) $id);
            } catch (\Throwable $e) {
                $this->error('No se pudo generar: ' . $e->getMessage());
                continue;
            } finally {
                if (DB::transactionLevel() > 0) {
                    DB::rollBack();
                }
            }

            $actual = $r['actual'];
            $v2 = $r['v2'];
            $tipo = $r['tipo'];

            $this->line("Tipo {$tipo} | versión actual: " . ($actual['identificacion']['version'] ?? '?')
                . ' → 2.0: ' . $v2['identificacion']['version']);

            $this->montos($actual, $v2);
            $this->items($actual, $v2);
            $this->datos($actual, $v2);
            $this->estructura($actual, $v2);
            $this->validar($tipo, $v2);

            $flags = JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION;
            file_put_contents("{$carpeta}/{$id}-actual.json", json_encode($actual, $flags));
            file_put_contents("{$carpeta}/{$id}-v2.json", json_encode($v2, $flags));
            $this->line("JSON guardados en storage/app/comparacion-dte/{$id}-actual.json y {$id}-v2.json");
        }

        $this->newLine();
        $this->comment('No se modificó ningún DTE (transacción revertida).');

        return self::SUCCESS;
    }

    private function montos(array $actual, array $v2): void
    {
        $a = $actual['resumen'] ?? [];
        $n = $v2['resumen'] ?? [];
        // En el CCF y la nota el IVA del JSON actual va en tributos
        $a['totalIva'] = $a['totalIva'] ?? ($a['tributos'][0]['valor'] ?? null);

        $filas = [];
        foreach (self::MONTOS as $campoV2 => $campoActual) {
            if (!array_key_exists($campoV2, $n) && !array_key_exists($campoActual, $a)) {
                continue;
            }
            $va = $a[$campoActual] ?? null;
            $vn = $n[$campoV2] ?? null;
            $igual = $va !== null && $vn !== null && abs((float) $va - (float) $vn) < 0.005;
            $filas[] = [
                $campoV2,
                $va === null ? '—' : number_format((float) $va, 2),
                $vn === null ? '(no va)' : number_format((float) $vn, 2),
                $igual ? 'igual' : ($vn === null || $va === null ? 'campo nuevo/quitado' : 'CAMBIA'),
            ];
        }
        $this->line('Montos del resumen:');
        $this->table(['Campo', 'Actual', '2.0', ''], $filas);
    }

    private function items(array $actual, array $v2): void
    {
        $ia = $actual['cuerpoDocumento'] ?? [];
        $in = $v2['cuerpoDocumento'] ?? [];
        $suma = fn ($items, $campo) => array_sum(array_map(fn ($i) => (float) ($i[$campo] ?? 0), $items));
        $campo = isset($in[0]['compra']) ? 'compra' : 'ventaGravada';

        $this->line(sprintf(
            'Ítems: %d actual / %d 2.0 | suma %s: %s actual / %s 2.0',
            count($ia), count($in), $campo,
            number_format($suma($ia, $campo), 8), number_format($suma($in, $campo), 8)
        ));

        if (isset($in[0]['totalIva'])) {
            $this->line('Suma totalIva por ítem (2.0): ' . number_format($suma($in, 'totalIva'), 2)
                . ' | totalIva resumen: ' . number_format((float) ($v2['resumen']['totalIva'] ?? 0), 2));
        }
    }

    private function datos(array $actual, array $v2): void
    {
        $ea = $actual['emisor'] ?? [];
        $en = $v2['emisor'] ?? [];
        $ra = $actual['receptor'] ?? ($actual['sujetoExcluido'] ?? []);
        $rn = $v2['receptor'] ?? [];

        $filas = [
            ['emisor.codEstable', ($ea['codEstableMH'] ?? '—') . ' (MH) / ' . ($ea['codEstable'] ?? '—'), $en['codEstable'] ?? '(no va)'],
            ['emisor.codPuntoVenta', ($ea['codPuntoVentaMH'] ?? '—') . ' (MH) / ' . ($ea['codPuntoVenta'] ?? '—'), $en['codPuntoVenta'] ?? '(no va)'],
            ['emisor.direccion', $this->dir($ea['direccion'] ?? null), $this->dir($en['direccion'] ?? null)],
            ['receptor.documento', ($ra['tipoDocumento'] ?? 'nit') . ': ' . ($ra['numDocumento'] ?? ($ra['nit'] ?? '—')),
                ($rn['tipoDocumento'] ?? 'nit') . ': ' . ($rn['numDocumento'] ?? ($rn['nit'] ?? '—'))],
            ['receptor.direccion', $this->dir($ra['direccion'] ?? null), $this->dir($rn['direccion'] ?? null)],
            ['tipoContingencia / motivo', json_encode([$actual['identificacion']['tipoContingencia'] ?? null, $actual['identificacion']['motivoContin'] ?? null]),
                json_encode([$v2['identificacion']['tipoContingencia'], $v2['identificacion']['motivoContin']])],
        ];
        $this->line('Datos que cambian de forma:');
        $this->table(['Dato', 'Actual', '2.0'], $filas);
    }

    private function estructura(array $actual, array $v2): void
    {
        $cambios = [];
        foreach (['' => null, 'emisor' => 'emisor', 'receptor' => 'receptor', 'resumen' => 'resumen'] as $etiqueta => $seccion) {
            $ka = array_keys($seccion ? ($actual[$seccion] ?? ($seccion === 'receptor' ? ($actual['sujetoExcluido'] ?? []) : [])) : $actual);
            $kn = array_keys($seccion ? ($v2[$seccion] ?? []) : $v2);
            $quitados = array_diff($ka, $kn);
            $nuevos = array_diff($kn, $ka);
            if ($quitados || $nuevos) {
                $cambios[] = ($etiqueta ?: 'raíz') . ': '
                    . ($quitados ? 'sale ' . implode(', ', $quitados) : '')
                    . ($quitados && $nuevos ? ' | ' : '')
                    . ($nuevos ? 'entra ' . implode(', ', $nuevos) : '');
            }
        }
        $this->line('Estructura: ' . ($cambios ? '' : 'sin cambios'));
        foreach ($cambios as $c) {
            $this->line('  • ' . $c);
        }
    }

    private function validar(string $tipo, array $v2): void
    {
        $carpeta = $this->option('esquemas') ?: base_path('resources/dte/esquemas');

        if (is_dir($carpeta)) {
            $ruta = rtrim($carpeta, '/\\') . DIRECTORY_SEPARATOR . self::ARCHIVOS[$tipo];
            if (!is_file($ruta)) {
                $this->warn("No existe {$ruta}");
                return;
            }
            $validador = new Validator(new SchemaLoader(new EsquemaDteParser()), 50, false);
            $res = $validador->validate(json_decode(json_encode($v2, JSON_PRESERVE_ZERO_FRACTION)), file_get_contents($ruta));
            if ($res->isValid()) {
                $this->info('Esquema oficial (' . self::ARCHIVOS[$tipo] . '): CUMPLE');
                return;
            }
            $this->error('Esquema oficial (' . self::ARCHIVOS[$tipo] . '): NO CUMPLE');
            foreach ((new ErrorFormatter())->formatKeyed($res->error()) as $campo => $mensajes) {
                foreach ((array) $mensajes as $m) {
                    $this->line("  {$campo}: " . (is_array($m) ? json_encode($m) : $m));
                }
            }
            return;
        }

        $res = app(ValidadorEsquemaDte::class)->validar($tipo, $v2);
        if (!$res['evaluado']) {
            $this->warn('Esquema: no se pudo validar (' . $res['motivo'] . ')');
        } elseif ($res['valido']) {
            $this->info("Esquema activo v{$res['version']}: CUMPLE");
        } else {
            $this->error("Esquema activo v{$res['version']}: NO CUMPLE");
            foreach (array_slice($res['errores'], 0, 20) as $e) {
                $this->line("  {$e['campo']}: {$e['mensaje']}");
            }
        }
    }

    private function dir(?array $d): string
    {
        return $d ? implode('/', [$d['departamento'] ?? '-', $d['municipio'] ?? '-', $d['distrito'] ?? '-']) . ' ' . ($d['complemento'] ?? '') : '—';
    }
}
