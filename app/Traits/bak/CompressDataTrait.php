<?php

namespace App\Traits;

trait CompressDataTrait
{
    /**
     * Normaliza a string:
     * - arrays/objetos -> JSON string (compacto, UTF-8)
     * - resources -> stream_get_contents
     * - null -> null
     */
    protected function normalizeToString(mixed $value, int $jsonFlags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES): ?string
    {
        if ($value === null) return null;

        if (is_resource($value)) {
            $value = stream_get_contents($value);
        }

        if (is_array($value) || is_object($value)) {
            return json_encode($value, $jsonFlags);
        }

        return (string) $value;
    }

    /**
     * Comprime en GZIP y codifica Base64.
     * - $force: true = siempre comprime; false = solo si supera $threshold bytes.
     */
    public function compressToStorable(mixed $value, bool $force = false, int $threshold = 65536): ?string
    {
        $str = $this->normalizeToString($value);

        if ($str === null) return null;

        if (!$force && strlen($str) <= $threshold) {
            // Pequeño -> guarda simple
            return $str;
        }

        $gz = gzencode($str, 9);
        if ($gz === false) {
            // Si por alguna razón falla la compresión, guarda simple
            return $str;
        }

        return base64_encode($gz);
    }

    /**
     * Helper específico para JSON:
     * - Acepta array/objeto/string (JSON).
     * - Siempre retorna Base64+GZIP (o simple si falla gz).
     */
    public function compressJson(mixed $json, int $jsonFlags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES): ?string
    {
        $str = $this->normalizeToString($json, $jsonFlags);
        if ($str === null) return null;

        $gz = gzencode($str, 9);
        if ($gz === false) return $str;

        return base64_encode($gz);
    }
}
