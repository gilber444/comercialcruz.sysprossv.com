<?php

namespace App\Helpers;

class Convertidor
{
    private static $unidades = [
        0 => 'cero',
        1 => 'uno',
        2 => 'dos',
        3 => 'tres',
        4 => 'cuatro',
        5 => 'cinco',
        6 => 'seis',
        7 => 'siete',
        8 => 'ocho',
        9 => 'nueve',
        10 => 'diez',
        11 => 'once',
        12 => 'doce',
        13 => 'trece',
        14 => 'catorce',
        15 => 'quince',
        16 => 'dieciséis',
        17 => 'diecisiete',
        18 => 'dieciocho',
        19 => 'diecinueve',
    ];

    private static $decenas = [
        2 => 'veinte',
        3 => 'treinta',
        4 => 'cuarenta',
        5 => 'cincuenta',
        6 => 'sesenta',
        7 => 'setenta',
        8 => 'ochenta',
        9 => 'noventa',
    ];

    private static $centenas = [
        1 => 'ciento',
        2 => 'doscientos',
        3 => 'trescientos',
        4 => 'cuatrocientos',
        5 => 'quinientos',
        6 => 'seiscientos',
        7 => 'setecientos',
        8 => 'ochocientos',
        9 => 'novecientos',
    ];

    private static $miles = [
        1 => 'mil',
        2 => 'dos mil',
        3 => 'tres mil',
        4 => 'cuatro mil',
        5 => 'cinco mil',
        6 => 'seis mil',
        7 => 'siete mil',
        8 => 'ocho mil',
        9 => 'nueve mil',
    ];

    public static function montoALetras($monto)
    {
        $partes = explode('.', $monto);
        $parteEntera = self::numeroALetras($partes[0]);

        // Ajuste para la parte decimal
        $parteDecimal = isset($partes[1]) ? $partes[1] : '00';
        if (strlen($parteDecimal) == 1) {
            $parteDecimal .= '0'; // Agrega un cero al final si solo tiene un dígito
        }

        return ucfirst($parteEntera) . " con $parteDecimal/100 USD";
    }

    public static function numeroALetras($num)
    {
        if ($num == 0) {
            return 'cero';
        }

        $resultado = '';

        if ($num >= 1000 && $num < 20000) {
            $millar = floor($num / 1000);
            if (isset(self::$miles[$millar])) {
                $resultado .= self::$miles[$millar] . ' ';
            }
            $num %= 1000;
        } elseif ($num >= 20000 && $num < 100000) {
            $decenaMil = floor($num / 10000);
            if (isset(self::$decenas[$decenaMil])) {
                $resultado .= self::$decenas[$decenaMil] . ' ';
            }
            $num %= 10000;
            if ($num >= 1000) {
                $millar = floor($num / 1000);
                if (isset(self::$miles[$millar])) {
                    $resultado .= self::$miles[$millar] . ' ';
                }
                $num %= 1000;
            }
        }

        if ($num >= 100 && $num < 1000) {
            $centena = floor($num / 100);
            if (isset(self::$centenas[$centena])) {
                $resultado .= self::$centenas[$centena] . ' ';
            }
            $num %= 100;
        }

        if ($num == 10) {
            $resultado .= 'diez ';
        } elseif ($num > 10 && $num < 20) {
            if (isset(self::$unidades[$num % 10])) {
                $resultado .= self::$unidades[$num % 10] . ' ';
            }
        } elseif ($num >= 20 && $num < 100) {
            $decena = floor($num / 10);
            if (isset(self::$decenas[$decena])) {
                $resultado .= self::$decenas[$decena] . ' ';
            }
            $num %= 10;
        }

        if ($num > 0 && $num < 20) {
            if ($num < 10) {
                if (isset(self::$unidades[$num])) {
                    $resultado .= self::$unidades[$num] . ' ';
                }
            } else {
                if (isset(self::$decenas[$num % 10])) {
                    $resultado .= self::$decenas[$num % 10] . ' ';
                }
            }
        }

        return trim($resultado);
    }
}
