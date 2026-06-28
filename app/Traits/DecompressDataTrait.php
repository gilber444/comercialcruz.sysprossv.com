<?php
namespace App\Traits;
trait DecompressDataTrait
{
    /**
     * Detecta si parece Base64 válido.
     */
    protected function looksLikeBase64(?string $value): bool
    {
        if ($value === null || $value === '') return false;
        // Base64 seguro: solo A-Z a-z 0-9 + / = y longitud múltiplo de 4
        if (preg_match('/^[A-Za-z0-9+\/=\r\n]+$/', $value) !== 1) return false;
        return (strlen(trim($value)) % 4) === 0;
    }
    /**
     * Intenta descomprimir (Base64 + GZIP).
     * - Si no es Base64 o falla, devuelve el valor original.
     */
    public function decompressFromStorable(mixed $stored): mixed
    {
        if ($stored === null) return null;
        // Normaliza a string por si viene stream/objeto
        if (is_resource($stored)) {
            $stored = stream_get_contents($stored);
        } elseif (is_array($stored) || is_object($stored)) {
            // Ya es estructura; no descomprimir
            return $stored;
        }
        $s = (string)$stored;
        if (!$this->looksLikeBase64($s)) {
            return $s; // No parece base64 → lo regresamos igual
        }
        $bin = base64_decode($s, true);
        if ($bin === false) return $s;
        $unz = @gzdecode($bin);
        if ($unz === false) return $s;
        return $unz;
    }
    /**
     * Descomprime y si es JSON válido, lo decodifica a array.
     * - $assoc = true => array; false => stdClass
     * - Si no es JSON válido, retorna string descomprimido.
     */
    public function decompressJson(?string $stored, bool $assoc = true): mixed
    {
        if ($stored === null) return null;
        $plain = $this->decompressFromStorable($stored);
        if (!is_string($plain)) return $plain;
        $decoded = json_decode($plain, $assoc);
        return (json_last_error() === JSON_ERROR_NONE) ? $decoded : $plain;
    }
}
