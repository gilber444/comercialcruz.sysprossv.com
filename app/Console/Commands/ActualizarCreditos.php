<?php

namespace App\Console\Commands;

use App\Models\Clientes;
use App\Models\Creditos;
use App\Models\Empleado;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

// [2026-03-26] Comando diario a las 2:00am
// Recalcula monto_credito de todos los empleados (tipopersona=3) según ventas al crédito del período
class ActualizarCreditos extends Command
{
    protected $signature   = 'credito:actualizar';
    protected $description = 'Recalcula monto_credito de empleados según ventas al crédito de la quincena actual';

    public function handle(): int
    {
        $hoy          = Carbon::today();
        $dia          = (int) $hoy->format('d');
        $mes          = (int) $hoy->format('m');
        $anio         = (int) $hoy->format('Y');

        // Determinar inicio y fin de la quincena actual
        // Q1: días 1-15  |  Q2: días 16-fin de mes
        if ($dia <= 15) {
            $inicioQ = Carbon::create($anio, $mes, 1);
            $finQ    = Carbon::create($anio, $mes, 15)->endOfDay();
        } else {
            $inicioQ = Carbon::create($anio, $mes, 16);
            $finQ    = $hoy->copy()->endOfDay();
        }

        $this->info("Procesando quincena: {$inicioQ->format('Y-m-d')} → {$finQ->format('Y-m-d')}");

        // Obtener todos los clientes que son empleados (tipoPersona = 3) y tienen crédito activo
        $clientes = Clientes::where('tipoPersona', 3)
            ->whereNotNull('dui')
            ->get();

        $procesados = 0;

        foreach ($clientes as $cliente) {
            // Buscar el empleado vinculado por DUI
            $empleado = Empleado::where('dui', $cliente->dui)
                ->where('estado', '1')
                ->first();

            if (!$empleado || !$empleado->salario) {
                // Sin empleado vinculado o sin salario, no se puede calcular
                continue;
            }

            // Calcular límite de crédito: 60% del salario quincenal
            $salarioQuincenal = round(floatval($empleado->salario) / 2, 2);
            $limiteCredito    = round($salarioQuincenal * ($cliente->porcentaje_credito / 100), 2);

            // Sumar créditos de ventas del empleado en la quincena actual
            $totalAcumulado = Creditos::where('empleado_id', $empleado->id)
                ->where('estado', 'Pendiente')
                ->whereBetween('fechaCredito', [$inicioQ->format('Y-m-d'), $finQ->format('Y-m-d')])
                ->sum('saldo'); // saldo = lo que aún debe

            // Calcular monto disponible restante
            $montoDisponible = max(0, $limiteCredito - $totalAcumulado);

            // Actualizar empleado
            $empleado->update([
                'limite_credito' => $limiteCredito,
                'monto_credito'  => $montoDisponible,
            ]);

            // Actualizar estado de crédito en cliente
            if ($totalAcumulado >= $limiteCredito) {
                // Superó el límite: bloquear crédito
                $cliente->update(['credito' => 'NO']);
                $this->line("  [{$empleado->nombre}] Bloqueado — acumulado: \${$totalAcumulado} / límite: \${$limiteCredito}");
            } else {
                // Dentro del límite: mantener crédito activo
                $cliente->update(['credito' => 'SI']);
            }

            $procesados++;
        }

        $this->info("Completado. Empleados procesados: {$procesados}");
        Log::info("credito:actualizar ejecutado — {$procesados} empleados procesados", [
            'quincena_inicio' => $inicioQ->format('Y-m-d'),
            'quincena_fin'    => $finQ->format('Y-m-d'),
        ]);

        return self::SUCCESS;
    }
}
