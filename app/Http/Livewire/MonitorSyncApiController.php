<?php

namespace App\Http\Livewire;

use App\Models\Feature;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Component;

/**
 * Monitor de las sincronizaciones por API (se usa en el VPS).
 * - Estado de cada caja: ultimo latido de cada comando de sync por API.
 * - Actividad: peticiones que recibio el VPS por servicio y tabla.
 * - Ultimos errores.
 * Los servicios nuevos aparecen solos (con su nombre de ruta) si su ruta esta en
 * el grupo con el middleware RegistrarSyncApi; aqui solo se les pone un nombre.
 */
class MonitorSyncApiController extends Component
{
    /** Nombre legible de cada comando de las cajas. */
    public const COMANDOS = [
        'sync:ventas-api' => 'Ventas, cajas y DTE',
        'sync:inventario-api' => 'Existencia y kardex',
        'sync:dtes-procesados-api' => 'DTE procesados',
        'sync:dtes-estado' => 'Estado de DTE (baja del VPS)',
    ];

    /** Nombre legible de cada servicio del VPS (nombre de ruta sin "api.sync."). */
    public const SERVICIOS = [
        'tablas.ventas' => 'Ventas y DTE',
        'inventario' => 'Existencia y kardex',
        'dtes.recibir' => 'DTE procesados',
        'dtes.faltantes' => 'DTE procesados (revision)',
        'dtes.resueltos' => 'DTE resueltos (bajada)',
    ];

    public int $sucursal = 0;

    public int $horas = 24;

    public function mount(): void
    {
        abort_unless(Auth::user()->can('MonitorSyncApi_Index') && Feature::isEnabled('monitor_sync_api'), 403);
    }

    public function render()
    {
        $hayTablas = Schema::hasTable('api_sync_registros') && Schema::hasTable('api_sync_latidos');
        $sucursales = DB::table('sucursales')->orderBy('id')->pluck('nombre', 'id')->all();
        $desde = now()->subHours(max(1, $this->horas));

        $latidos = collect();
        $actividad = collect();
        $errores = collect();
        $totales = ['peticiones' => 0, 'guardados' => 0, 'errores' => 0, 'fallidas' => 0];

        if ($hayTablas) {
            $latidos = DB::table('api_sync_latidos')
                ->when($this->sucursal > 0, fn ($q) => $q->where('sucursal', $this->sucursal))
                ->orderBy('sucursal')->orderBy('comando')->get()
                ->map(fn ($l) => $this->conEstado($l))
                ->groupBy('sucursal');

            $actividad = DB::table('api_sync_registros')
                ->where('created_at', '>=', $desde)
                ->when($this->sucursal > 0, fn ($q) => $q->where('sucursal', $this->sucursal))
                ->groupBy('sucursal', 'servicio', 'tabla')
                ->orderBy('sucursal')->orderBy('servicio')->orderBy('tabla')
                ->get([
                    'sucursal', 'servicio', 'tabla',
                    DB::raw('COUNT(*) as peticiones'),
                    DB::raw('SUM(recibidas) as recibidas'),
                    DB::raw('SUM(guardados) as guardados'),
                    DB::raw('SUM(errores) as errores'),
                    DB::raw('SUM(pendientes) as pendientes'),
                    DB::raw('SUM(CASE WHEN http >= 400 THEN 1 ELSE 0 END) as fallidas'),
                    DB::raw('AVG(ms) as ms'),
                    DB::raw('MAX(created_at) as ultima'),
                ]);

            foreach ($actividad as $a) {
                $totales['peticiones'] += (int) $a->peticiones;
                $totales['guardados'] += (int) $a->guardados;
                $totales['errores'] += (int) $a->errores;
                $totales['fallidas'] += (int) $a->fallidas;
            }

            $errores = DB::table('api_sync_registros')
                ->where('created_at', '>=', $desde)
                ->when($this->sucursal > 0, fn ($q) => $q->where('sucursal', $this->sucursal))
                ->where(fn ($q) => $q->where('errores', '>', 0)->orWhere('http', '>=', 400))
                ->orderByDesc('id')->limit(50)->get();
        }

        $cajasAlDia = $latidos->filter(fn ($grupo) => $grupo->every(fn ($l) => $l->color === 'success'))->count();

        return view('livewire.sync.monitor-sync-api', [
            'hayTablas' => $hayTablas,
            'esVps' => config('app.modo', 'local') === 'vps',
            'sucursales' => $sucursales,
            'latidos' => $latidos,
            'actividad' => $actividad,
            'errores' => $errores,
            'totales' => $totales,
            'cajasAlDia' => $cajasAlDia,
            'actualizado' => now()->format('H:i:s'),
        ])->extends('layouts.theme.app')->section('content');
    }

    /** Semaforo del latido: verde al dia, amarillo atrasado, rojo con error o sin contacto. */
    private function conEstado(object $l): object
    {
        $minutos = $l->ultimo_at ? Carbon::parse($l->ultimo_at)->diffInMinutes(now()) : null;
        if ($minutos === null || $minutos > 30) {
            [$l->color, $l->estado] = ['danger', 'Sin contacto'];
        } elseif (!$l->ok) {
            [$l->color, $l->estado] = ['danger', 'Con error' . ($l->fallos_seguidos > 1 ? " ({$l->fallos_seguidos} seguidos)" : '')];
        } elseif ($minutos > 5) {
            [$l->color, $l->estado] = ['warning', 'Atrasado'];
        } else {
            [$l->color, $l->estado] = ['success', 'Al dia'];
        }
        $l->nombre = self::COMANDOS[$l->comando] ?? $l->comando;
        $l->hace = $l->ultimo_at ? Carbon::parse($l->ultimo_at)->locale('es')->diffForHumans() : 'nunca';

        return $l;
    }

    public static function nombreServicio(string $servicio): string
    {
        return self::SERVICIOS[$servicio] ?? $servicio;
    }
}
