<?php

namespace App\Services;

use App\Traits\GenerarToken;
use App\Models\Tocken;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;
use Throwable;

class DailyTokenService
{
    use GenerarToken; // usa tu trait sin cambiarlo

    /**
     * Asegura el token diario compartido:
     * - Si existe en VPS para hoy → lo trae y refleja en LOCAL.
     * - Si no existe → genera en LOCAL (Trait) y lo sube al VPS.
     */
    public function ensureForToday(): string
    {
        $today = Carbon::now('America/El_Salvador')->toDateString();


        // 1) ¿Ya existe en el VPS para hoy?
        $vpsRow = Tocken::on('vpsmysql')
            ->whereDate('fecha', $today)
            ->where('estado', 'ok')
            ->orderByDesc('id')
            ->first();

        if ($vpsRow) {
            // espejo en LOCAL y devolver
            $local =Tocken::updateOrCreate(
                ['fecha' => $today],
                ['tocken' => $vpsRow->tocken, 'estado' => $vpsRow->estado, 'json' => $vpsRow->json]
            );

            $local->forceFill(['id_vps' => $vpsRow->id])->save(); //
            return $vpsRow->tocken;
        }

        // 2) No hay en VPS: generar en LOCAL con tu Trait
        $token = $this->GenerarToken(); // 👈 llama a tu método existente

        $localRow = Tocken::on('localmysql')
            ->whereDate('fecha', $today)
            ->where('estado', 'ok')
            ->orderByDesc('id')
            ->first();

        // 3) subir a VPS (upsert)
        try {
            Tocken::on('vpsmysql')->updateOrCreate(
                ['fecha' => $today],
                ['tocken' => $token,
                'estado' => $localRow->estado ?? 'OK',
                'json' => $localRow->json]
            );
        } catch (\Throwable $e) {
            // si otra tienda subió primero (duplicado), lo ignoramos
            if (str_contains($e->getMessage(), '23000')) {
                // ok, ya existe; no pasa nada
            } else {
                throw $e;
            }
        }

        // ⭐ leer el ID real en VPS y guardarlo en la fila local
        $vpsRow = Tocken::on('vpsmysql')
            ->whereDate('fecha', $today)
            ->where('estado', 'ok')
            ->orderByDesc('id')
            ->first();

        if ($vpsRow && $localRow) {
            $localRow->forceFill(['id_vps' => $vpsRow->id])->save(); // ⭐
        }
        return $token;
    }
}
