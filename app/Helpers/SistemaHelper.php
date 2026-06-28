<?php

namespace App\Helpers;

use App\Models\Sucursales;
use Illuminate\Support\Facades\Auth;

class SistemaHelper
{
    /**
     * ¿El sistema está corriendo en el VPS?
     * Se controla mediante APP_MODO=vps en el .env del servidor.
     */
    public static function estaEnVps(): bool
    {
        return config('app.modo', 'local') === 'vps';
    }

    /**
     * ¿La sucursal está configurada como local (Laragon)?
     */
    public static function sucursalEsLocal(int $sucursalId): bool
    {
        return Sucursales::where('id', $sucursalId)->value('modo') === 'local';
    }

    /**
     * ¿Debe bloquearse la operación sobre esta sucursal?
     *
     * Bloqueo = los tres deben cumplirse:
     *   1. El usuario NO es perfil exento (Super, Administrador, Gerente General, Auditor, Auxiliar de Auditor)
     *   2. Estamos en el VPS
     *   3. La sucursal objetivo es local
     */
    public static function operacionBloqueada(int $sucursalId): bool
    {
        if (!$sucursalId) return false;

        $perfilesExentos = ['Super', 'Administrador', 'Gerente General', 'Auditor', 'Auxiliar de Auditor', 'Domicilio', 'domicilio'];
        if (in_array(Auth::user()->profile, $perfilesExentos, true)) return false;

        if (!static::estaEnVps()) return false;
        return static::sucursalEsLocal($sucursalId);
    }
}
