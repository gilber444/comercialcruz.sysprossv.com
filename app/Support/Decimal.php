<?php

namespace App\Support;

/**
 * Aritmetica decimal determinista para importes DTE.
 * Trabaja internamente con enteros escalados; no depende de float ni de BCMath.
 *
 * Copiado del proyecto fuente y CORREGIDO: en el original div() multiplicaba
 * el dividendo escalado por 10^8 otra vez (A * 10^16), lo que desborda el
 * entero de 64 bits desde 922.34 y lanzaba TypeError (p. ej. div('1000',
 * '1.13')); mul() desbordaba igual con operandos grandes, y format()/round()
 * pasaban por float. Ahora todas las operaciones se hacen por partes con
 * enteros y el texto se arma sin float. La API publica no cambia.
 */
final class Decimal
{
    private const SCALE = 8;

    public static function add(...$values): string
    {
        $total = 0;
        foreach ($values as $value) $total += self::integer($value);
        return self::format($total);
    }

    public static function sub($left, $right): string
    {
        return self::format(self::integer($left) - self::integer($right));
    }

    public static function mul($left, $right): string
    {
        $a = self::integer($left);
        $b = self::integer($right);
        $sign = (($a < 0) xor ($b < 0)) ? -1 : 1;
        $a = abs($a);
        $b = abs($b);
        $factor = self::factor();

        // a*b/10^8 = a*bEntero + aEntero*bFraccion + aFraccion*bFraccion/10^8
        $bEntero = intdiv($b, $factor);
        $bFraccion = $b % $factor;
        $aEntero = intdiv($a, $factor);
        $aFraccion = $a % $factor;

        $resultado = ($a * $bEntero)
            + ($aEntero * $bFraccion)
            + self::roundInteger($aFraccion * $bFraccion, $factor);

        return self::format($sign * $resultado);
    }

    public static function div($left, $right): string
    {
        $denominator = self::integer($right);
        if ($denominator === 0) throw new \InvalidArgumentException('Division decimal entre cero.');
        $numerator = self::integer($left);
        $sign = (($numerator < 0) xor ($denominator < 0)) ? -1 : 1;
        $numerator = abs($numerator);
        $denominator = abs($denominator);

        // Division larga: parte entera y luego los 8 decimales uno por uno,
        // sin multiplicar el dividendo por 10^8 (eso desbordaba).
        $cociente = intdiv($numerator, $denominator) * self::factor();
        $resto = $numerator % $denominator;
        for ($posicion = self::SCALE - 1; $posicion >= 0; $posicion--) {
            $resto *= 10;
            $cociente += intdiv($resto, $denominator) * (10 ** $posicion);
            $resto %= $denominator;
        }
        if ($resto * 2 >= $denominator) $cociente++;

        return self::format($sign * $cociente);
    }

    public static function round($value, int $decimals = 2): string
    {
        if ($decimals < 0 || $decimals > self::SCALE) throw new \InvalidArgumentException('Escala decimal invalida.');
        $unit = 10 ** (self::SCALE - $decimals);
        // Resultado en pasos de 10^-$decimals.
        return self::texto(self::roundInteger(self::integer($value), $unit), $decimals);
    }

    private static function integer($value): int
    {
        $text = str_replace(',', '.', trim((string) $value));
        if ($text === '' || !is_numeric($text)) return 0;
        // Notacion cientifica (un float pequeno convertido a texto, p. ej. 1.0E-5).
        if (stripos($text, 'e') !== false) $text = sprintf('%.' . self::SCALE . 'F', (float) $text);
        $negative = str_starts_with($text, '-');
        $text = ltrim($text, '+-');
        [$whole, $fraction] = array_pad(explode('.', $text, 2), 2, '');
        $fraction = substr(str_pad(preg_replace('/\D/', '', $fraction), self::SCALE, '0'), 0, self::SCALE);
        $result = ((int) ($whole ?: 0) * self::factor()) + (int) $fraction;
        return $negative ? -$result : $result;
    }

    private static function roundInteger(int $value, int $unit): int
    {
        $sign = $value < 0 ? -1 : 1;
        $value = abs($value);
        $quotient = intdiv($value, $unit);
        return $sign * ($quotient + (($value % $unit) * 2 >= $unit ? 1 : 0));
    }

    private static function factor(): int { return 100000000; }

    private static function format(int $value): string
    {
        return self::texto($value, self::SCALE);
    }

    /** Texto exacto de un entero con $decimales implicitos, sin pasar por float. */
    private static function texto(int $valor, int $decimales): string
    {
        $signo = $valor < 0 ? '-' : '';
        $valor = abs($valor);
        if ($decimales === 0) return $signo . $valor;
        $divisor = 10 ** $decimales;
        return $signo . intdiv($valor, $divisor) . '.' . str_pad((string) ($valor % $divisor), $decimales, '0', STR_PAD_LEFT);
    }
}
