<?php

namespace App\Support;

use JsonException;

class JsonComprimido
{
    private const PREFIJO = 'gz64:';

    public static function comprimir($contenido): ?string
    {
        if ($contenido === null || $contenido === '') {
            return $contenido === null ? null : '';
        }

        if (is_array($contenido) || is_object($contenido)) {
            $contenido = json_encode($contenido, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        }

        $contenido = (string) $contenido;

        if (self::extraerToken($contenido) !== null) {
            return $contenido;
        }

        $comprimido = gzencode($contenido, 9);

        if ($comprimido === false) {
            return $contenido;
        }

        // Las comillas hacen que el valor siga siendo valido en columnas JSON
        // de MySQL, sin necesidad de esperar una migracion de tipo de columna.
        $envuelto = json_encode(self::PREFIJO . base64_encode($comprimido), JSON_THROW_ON_ERROR);

        return strlen($envuelto) < strlen($contenido) ? $envuelto : $contenido;
    }

    public static function descomprimir($contenido): ?string
    {
        if ($contenido === null) {
            return null;
        }

        if (is_array($contenido) || is_object($contenido)) {
            return json_encode($contenido, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        }

        $contenido = (string) $contenido;
        $token = self::extraerToken($contenido);

        if ($token !== null) {
            $binario = base64_decode(substr($token, strlen(self::PREFIJO)), true);
            $json = $binario === false ? false : @gzdecode($binario);

            return $json === false ? null : $json;
        }

        // Compatibilidad con intentos historicos que guardaron zlib binario.
        $json = @gzuncompress($contenido);
        if ($json !== false) {
            return $json;
        }

        if (strlen($contenido) > 4) {
            $json = @gzuncompress(substr($contenido, 4));
            if ($json !== false) {
                return $json;
            }
        }

        return $contenido;
    }

    public static function estaComprimido($contenido): bool
    {
        return is_string($contenido) && self::extraerToken($contenido) !== null;
    }

    private static function extraerToken(string $contenido): ?string
    {
        if (str_starts_with($contenido, self::PREFIJO)) {
            return $contenido;
        }

        try {
            $decodificado = json_decode($contenido, true, 2, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            return null;
        }

        return is_string($decodificado) && str_starts_with($decodificado, self::PREFIJO)
            ? $decodificado
            : null;
    }
}
