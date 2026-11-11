<?php

namespace App\Support;

use App\Models\EsquemaDte;

/**
 * Version de los eventos DTE (invalidacion y contingencia).
 *
 * Fuente unica para identificacion.version del JSON y para la version del
 * sobre que se envia a recepcion: si salen de lugares distintos, Hacienda
 * rechaza el evento. Manda el esquema activo en esquema_dtes (claves 'INV' y
 * 'CON'); si la tabla no existe, no hay conexion o no hay esquema cargado se
 * usa la version oficial vigente. Un problema de BD nunca debe impedir
 * invalidar ni declarar una contingencia.
 */
class VersionEventoDte
{
    /** Evento de invalidacion: schema oficial v3. */
    public const INVALIDACION = 3;

    /** Evento de contingencia: schema oficial v4. */
    public const CONTINGENCIA = 4;

    public static function invalidacion(): int
    {
        return self::resolver('INV', self::INVALIDACION);
    }

    public static function contingencia(): int
    {
        return self::resolver('CON', self::CONTINGENCIA);
    }

    /**
     * Version declarada dentro de un documento firmado (JWS compacto) o de un
     * JSON plano. Devuelve null si no se puede leer.
     */
    public static function desdeDocumento($documento): ?int
    {
        if (is_array($documento)) {
            $datos = $documento;
        } else {
            $texto = (string) $documento;
            $segmentos = explode('.', $texto);

            if (count($segmentos) === 3) {
                $payload = strtr($segmentos[1], '-_', '+/');
                $payload .= str_repeat('=', (4 - strlen($payload) % 4) % 4);
                $texto = (string) base64_decode($payload, true);
            }

            $datos = json_decode($texto, true);
        }

        $version = is_array($datos) ? ($datos['identificacion']['version'] ?? null) : null;

        return is_numeric($version) ? (int) $version : null;
    }

    private static function resolver(string $clave, int $respaldo): int
    {
        try {
            $version = EsquemaDte::versionVigente($clave);

            if ($version !== null && $version > 0) {
                return (int) $version;
            }
        } catch (\Throwable $e) {
            // Sin tabla o sin conexion se usa la version oficial de respaldo.
        }

        return $respaldo;
    }
}
