<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Limpieza unica del correo del receptor antes de armar, firmar o transmitir
 * el JSON del DTE.
 *
 * Hacienda rechaza el documento completo por un correo con un espacio, una
 * tilde o una coma en lugar del punto, y el mensaje no dice cual es el campo.
 * Centralizado aqui para que ningun generador tenga su propia version.
 */
class NormalizadorCorreoDte
{
    /**
     * Devuelve el correo limpio. No valida: usar esValido() para eso.
     */
    public static function normalizar($correo): string
    {
        // Str::ascii resuelve tildes y convierte la ñ en n.
        $limpio = Str::ascii(trim((string) $correo));

        $limpio = str_replace(',', '.', $limpio);

        // Espacios internos accidentales del digitado. Se quitan solo cuando
        // el resto ya parece un correo, para no fabricar uno inexistente.
        $sinEspacios = preg_replace('/\s+/', '', $limpio);
        if ($sinEspacios !== '' && filter_var($sinEspacios, FILTER_VALIDATE_EMAIL)) {
            $limpio = $sinEspacios;
        }

        return strtolower(trim($limpio));
    }

    public static function esValido($correo): bool
    {
        $limpio = self::normalizar($correo);

        return $limpio !== '' && filter_var($limpio, FILTER_VALIDATE_EMAIL) !== false;
    }
}
