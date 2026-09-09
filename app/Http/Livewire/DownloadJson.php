<?php

namespace App\Http\Livewire;

use App\Http\Controllers\ExportDteController;
use App\Models\dte;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Livewire\Component;
use RuntimeException;
use Throwable;
use ZipArchive;

class DownloadJson extends Component
{
    private const CACHE_MINUTES = 60;

    public $fromDate;
    public $toDate;
    public $tipoDte = 'todos';
    public $formato = 'json';
    public $procesando = false;
    public $progreso = 0;
    public $procesados = 0;
    public $total = 0;
    public $errores = 0;
    public $tokenExportacion;

    public function mount(): void
    {
        $this->fromDate = now()->startOfMonth()->format('Y-m-d');
        $this->toDate = now()->format('Y-m-d');
    }

    public function render()
    {
        return view('livewire.dtes.download-json')->extends('layouts.theme.app')->section('content');
    }

    public function Procesar(): void
    {
        if ($this->procesando) {
            return;
        }

        $this->validate([
            'fromDate' => 'required|date',
            'toDate' => 'required|date|after_or_equal:fromDate',
            'tipoDte' => 'required|in:todos,01,03,05,14',
            'formato' => 'required|in:json,pdf,ambos',
        ], [
            'fromDate.required' => 'La fecha inicial es obligatoria.',
            'fromDate.date' => 'La fecha inicial debe ser valida.',
            'toDate.required' => 'La fecha final es obligatoria.',
            'toDate.date' => 'La fecha final debe ser valida.',
            'toDate.after_or_equal' => 'La fecha final debe ser igual o posterior a la fecha inicial.',
            'tipoDte.in' => 'El tipo de documento seleccionado no es valido.',
            'formato.in' => 'El formato seleccionado no es valido.',
        ]);

        $user = Auth::user();
        $query = dte::query()
            ->join('tipo_documentos as td', 'td.id', '=', 'dtes.tipoDte')
            ->where('dtes.empresa', $user->empresa)
            ->whereBetween('dtes.fecEmi', [$this->fromDate, $this->toDate]);

        if ($this->tipoDte !== 'todos') {
            $query->where('td.codigo', $this->tipoDte);
        } else {
            $query->whereIn('td.codigo', ['01', '03', '05', '14']);
        }

        $ids = $query->orderBy('dtes.fecEmi')->orderBy('dtes.id')->pluck('dtes.id')->all();

        if (empty($ids)) {
            $this->emit('item-error', 'No hay DTE del tipo y rango de fechas seleccionados.');
            return;
        }

        $this->limpiarExportacionesAntiguas((int) $user->id);

        $token = (string) Str::uuid();
        $directory = storage_path('app/dte-exports/' . (int) $user->id);
        File::ensureDirectoryExists($directory);
        $path = $directory . DIRECTORY_SEPARATOR . $token . '.zip';

        $zip = new ZipArchive();
        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            $this->emit('item-error', 'No se pudo preparar el archivo ZIP.');
            return;
        }
        $zip->close();

        Cache::put($this->cacheKey($token), [
            'user_id' => (int) $user->id,
            'empresa_id' => (int) $user->empresa,
            'ids' => array_map('intval', $ids),
            'posicion' => 0,
            'total' => count($ids),
            'formato' => $this->formato,
            'desde' => $this->fromDate,
            'hasta' => $this->toDate,
            'path' => $path,
            'errores' => [],
            'listo' => false,
        ], now()->addMinutes(self::CACHE_MINUTES));

        $this->tokenExportacion = $token;
        $this->procesando = true;
        $this->progreso = 0;
        $this->procesados = 0;
        $this->total = count($ids);
        $this->errores = 0;
        $this->emit('procesar-lote-descarga');
    }

    public function procesarLoteDescarga(): void
    {
        if (!$this->procesando || !$this->tokenExportacion) {
            return;
        }

        $key = $this->cacheKey($this->tokenExportacion);
        $job = Cache::get($key);
        $user = Auth::user();

        if (!$job || (int) $job['user_id'] !== (int) $user->id || (int) $job['empresa_id'] !== (int) $user->empresa) {
            $this->detenerConError('La exportacion vencio o no pertenece al usuario actual.');
            return;
        }

        $cantidad = $job['formato'] === 'json' ? 20 : ($job['formato'] === 'pdf' ? 2 : 1);
        $idsLote = array_slice($job['ids'], (int) $job['posicion'], $cantidad);

        $zip = new ZipArchive();
        if ($zip->open($job['path'], ZipArchive::CREATE) !== true) {
            $this->detenerConError('No se pudo continuar escribiendo el archivo ZIP.');
            return;
        }

        $columnas = ['id', 'codigoGeneracion', 'numeroControl'];
        if (in_array($job['formato'], ['json', 'ambos'], true)) {
            $columnas[] = 'jsonDte';
        }

        $documentos = dte::query()
            ->where('empresa', $job['empresa_id'])
            ->whereIn('id', $idsLote)
            ->get($columnas)
            ->keyBy('id');

        foreach ($idsLote as $id) {
            try {
                $documento = $documentos->get($id);
                if (!$documento) {
                    throw new RuntimeException('El documento ya no existe o no pertenece a la empresa.');
                }

                $nombre = $this->nombreSeguro($documento->codigoGeneracion ?: $documento->numeroControl ?: ('dte-' . $id));

                if (in_array($job['formato'], ['json', 'ambos'], true)) {
                    if (!is_string($documento->jsonDte) || trim($documento->jsonDte) === '') {
                        throw new RuntimeException('El documento no tiene JSON.');
                    }
                    $carpeta = $job['formato'] === 'ambos' ? 'JSON/' : '';
                    if (!$zip->addFromString($carpeta . $nombre . '.json', $documento->jsonDte)) {
                        throw new RuntimeException('No se pudo agregar el JSON al ZIP.');
                    }
                }

                if (in_array($job['formato'], ['pdf', 'ambos'], true)) {
                    $pdf = app(ExportDteController::class)->generarPDF($documento->codigoGeneracion);
                    if (!is_string($pdf) || $pdf === '') {
                        throw new RuntimeException('No se pudo generar el PDF.');
                    }
                    $carpeta = $job['formato'] === 'ambos' ? 'PDF/' : '';
                    if (!$zip->addFromString($carpeta . $nombre . '.pdf', $pdf)) {
                        throw new RuntimeException('No se pudo agregar el PDF al ZIP.');
                    }
                }
            } catch (Throwable $e) {
                $job['errores'][] = 'DTE ' . $id . ': ' . $e->getMessage();
            }
        }

        $zip->close();
        $job['posicion'] = min((int) $job['total'], (int) $job['posicion'] + count($idsLote));
        $this->procesados = $job['posicion'];
        $this->errores = count($job['errores']);
        $this->progreso = $job['total'] > 0 ? (int) floor(($job['posicion'] * 100) / $job['total']) : 100;

        if ($job['posicion'] < $job['total']) {
            Cache::put($key, $job, now()->addMinutes(self::CACHE_MINUTES));
            $this->emit('procesar-lote-descarga');
            return;
        }

        if (!empty($job['errores'])) {
            $zip = new ZipArchive();
            if ($zip->open($job['path'], ZipArchive::CREATE) === true) {
                $zip->addFromString('ERRORES.txt', implode(PHP_EOL, $job['errores']));
                $zip->close();
            }
        }

        $job['listo'] = true;
        $job['nombre'] = sprintf('dte_%s_%s_%s.zip', $job['formato'], $job['desde'], $job['hasta']);
        Cache::put($key, $job, now()->addMinutes(self::CACHE_MINUTES));

        $this->procesando = false;
        $this->progreso = 100;
        $this->emit('descarga-dte-lista', route('download.dte.zip', ['token' => $this->tokenExportacion]));
    }

    private function detenerConError(string $mensaje): void
    {
        $this->procesando = false;
        $this->emit('item-error', $mensaje);
    }

    private function cacheKey(string $token): string
    {
        return 'dte-export:' . $token;
    }

    private function nombreSeguro(string $nombre): string
    {
        $nombre = preg_replace('/[^A-Za-z0-9_-]/', '-', $nombre);
        return trim((string) $nombre, '-') ?: 'dte';
    }

    private function limpiarExportacionesAntiguas(int $userId): void
    {
        $directory = storage_path('app/dte-exports/' . $userId);
        if (!File::isDirectory($directory)) {
            return;
        }

        foreach (File::files($directory) as $file) {
            if ($file->getMTime() < now()->subHours(2)->timestamp) {
                File::delete($file->getPathname());
            }
        }
    }
}
