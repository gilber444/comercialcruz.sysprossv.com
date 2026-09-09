<?php

namespace App\Helpers;

class Convertidor
{
    private const UNIDADES = [
        0 => 'cero', 1 => 'uno', 2 => 'dos', 3 => 'tres', 4 => 'cuatro',
        5 => 'cinco', 6 => 'seis', 7 => 'siete', 8 => 'ocho', 9 => 'nueve',
        10 => 'diez', 11 => 'once', 12 => 'doce', 13 => 'trece', 14 => 'catorce',
        15 => 'quince', 16 => 'dieciséis', 17 => 'diecisiete', 18 => 'dieciocho',
        19 => 'diecinueve', 20 => 'veinte', 21 => 'veintiuno', 22 => 'veintidós',
        23 => 'veintitrés', 24 => 'veinticuatro', 25 => 'veinticinco',
        26 => 'veintiséis', 27 => 'veintisiete', 28 => 'veintiocho', 29 => 'veintinueve',
    ];

    private const DECENAS = [
        3 => 'treinta', 4 => 'cuarenta', 5 => 'cincuenta', 6 => 'sesenta',
        7 => 'setenta', 8 => 'ochenta', 9 => 'noventa',
    ];

    private const CENTENAS = [
        1 => 'ciento', 2 => 'doscientos', 3 => 'trescientos', 4 => 'cuatrocientos',
        5 => 'quinientos', 6 => 'seiscientos', 7 => 'setecientos',
        8 => 'ochocientos', 9 => 'novecientos',
    ];

    private const ESCALAS = [
        1 => ['millón', 'millones'],
        2 => ['billón', 'billones'],
        3 => ['trillón', 'trillones'],
        4 => ['cuatrillón', 'cuatrillones'],
        5 => ['quintillón', 'quintillones'],
        6 => ['sextillón', 'sextillones'],
        7 => ['septillón', 'septillones'],
        8 => ['octillón', 'octillones'],
    ];

    public static function montoALetras($monto, string $moneda = 'USD'): string
    {
        [$negativo, $entero, $centavos] = self::normalizarMonto($monto);
        $letras = self::numeroALetras(($negativo ? '-' : '') . $entero);

        return ucfirst($letras) . ' con ' . $centavos . '/100 ' . strtoupper($moneda);
    }

    public static function numeroALetras($numero): string
    {
        $texto = trim((string) $numero);
        $negativo = str_starts_with($texto, '-');
        $texto = preg_replace('/\D/', '', ltrim($texto, '+-')) ?: '0';
        $texto = ltrim($texto, '0');

        if ($texto === '') {
            return $negativo ? 'menos cero' : 'cero';
        }

        $grupos = [];
        while ($texto !== '') {
            array_unshift($grupos, (int) substr($texto, -3));
            $texto = substr($texto, 0, -3);
        }

        $cantidadGrupos = count($grupos);
        $partes = [];

        foreach ($grupos as $posicion => $grupo) {
            if ($grupo === 0) {
                continue;
            }

            $indice = $cantidadGrupos - $posicion - 1;
            $partes[] = self::grupoConEscala($grupo, $indice);
        }

        $resultado = implode(' ', $partes);

        return $negativo ? 'menos ' . $resultado : $resultado;
    }

    private static function grupoConEscala(int $grupo, int $indice): string
    {
        if ($indice === 0) {
            return self::grupo999($grupo);
        }

        if ($indice === 1) {
            return $grupo === 1 ? 'mil' : self::apocope(self::grupo999($grupo)) . ' mil';
        }

        $escala = intdiv($indice, 2);
        $nombres = self::ESCALAS[$escala] ?? ["10^" . ($indice * 3), "10^" . ($indice * 3)];

        if ($indice % 2 === 0) {
            return $grupo === 1
                ? 'un ' . $nombres[0]
                : self::apocope(self::grupo999($grupo)) . ' ' . $nombres[1];
        }

        $prefijo = $grupo === 1 ? 'mil' : self::apocope(self::grupo999($grupo)) . ' mil';

        return $prefijo . ' ' . $nombres[1];
    }

    private static function grupo999(int $numero): string
    {
        if ($numero === 100) {
            return 'cien';
        }

        $partes = [];

        if ($numero >= 100) {
            $partes[] = self::CENTENAS[intdiv($numero, 100)];
            $numero %= 100;
        }

        if ($numero >= 30) {
            $decena = self::DECENAS[intdiv($numero, 10)];
            $unidad = $numero % 10;
            $partes[] = $unidad === 0 ? $decena : $decena . ' y ' . self::UNIDADES[$unidad];
        } elseif ($numero > 0) {
            $partes[] = self::UNIDADES[$numero];
        }

        return implode(' ', $partes);
    }

    private static function apocope(string $texto): string
    {
        $texto = preg_replace('/veintiuno$/u', 'veintiún', $texto);
        $texto = preg_replace('/ y uno$/u', ' y un', $texto);

        return preg_replace('/uno$/u', 'un', $texto);
    }

    private static function normalizarMonto($monto): array
    {
        if (is_float($monto)) {
            $texto = rtrim(rtrim(number_format($monto, 10, '.', ''), '0'), '.');
        } else {
            $texto = trim((string) $monto);
        }

        $negativo = str_starts_with($texto, '-');
        $texto = preg_replace('/[^0-9.,]/', '', $texto) ?: '0';
        $ultimoPunto = strrpos($texto, '.');
        $ultimaComa = strrpos($texto, ',');
        $separador = false;

        if ($ultimoPunto !== false || $ultimaComa !== false) {
            $separador = max($ultimoPunto === false ? -1 : $ultimoPunto, $ultimaComa === false ? -1 : $ultimaComa);
        }

        if ($separador === false) {
            $entero = preg_replace('/\D/', '', $texto) ?: '0';
            $decimal = '';
        } else {
            $entero = preg_replace('/\D/', '', substr($texto, 0, $separador)) ?: '0';
            $decimal = preg_replace('/\D/', '', substr($texto, $separador + 1));
        }

        $entero = ltrim($entero, '0') ?: '0';
        $decimal = str_pad($decimal, 3, '0');
        $centavos = (int) substr($decimal, 0, 2);

        if ((int) $decimal[2] >= 5) {
            $centavos++;
            if ($centavos === 100) {
                $centavos = 0;
                $entero = self::incrementarCadena($entero);
            }
        }

        return [$negativo && ($entero !== '0' || $centavos !== 0), $entero, str_pad((string) $centavos, 2, '0', STR_PAD_LEFT)];
    }

    private static function incrementarCadena(string $numero): string
    {
        $acarreo = 1;

        for ($i = strlen($numero) - 1; $i >= 0 && $acarreo; $i--) {
            $digito = ((int) $numero[$i]) + $acarreo;
            $numero[$i] = (string) ($digito % 10);
            $acarreo = intdiv($digito, 10);
        }

        return $acarreo ? '1' . $numero : $numero;
    }
}
