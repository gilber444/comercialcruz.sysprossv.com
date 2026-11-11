<?php

namespace App\Services;

use App\Models\dte;
use App\Models\RecepcionDte;
use Carbon\Carbon;
use Carbon\CarbonInterface;

class PlazoInvalidacionDteService
{
    /** Códigos CAT-002 sujetos al límite del décimo día hábil. */
    private const DIEZ_DIAS_HABILES = ['03', '04', '05', '06', '07', '08', '09', '15'];

    /** Factura, factura de exportación y factura de sujeto excluido. */
    private const TRES_MESES = ['01', '11', '14'];

    public function fechaSello(dte $dte): Carbon
    {
        $recepcion = RecepcionDte::query()
            ->where('dte', $dte->id)
            ->whereNotNull('selloRecibido')
            ->orderByDesc('fhProcesamiento')
            ->first();

        if ($recepcion?->fhProcesamiento) {
            return Carbon::parse($recepcion->fhProcesamiento);
        }

        // Compatibilidad con registros históricos que no guardaron la recepción.
        return Carbon::parse($dte->fecEmi . ' ' . ($dte->horEmi ?: '00:00:00'));
    }

    public function limite(dte $dte): Carbon
    {
        $fechaSello = $this->fechaSello($dte);
        $codigo = $this->codigoTipo($dte);

        if (in_array($codigo, self::DIEZ_DIAS_HABILES, true)) {
            return $this->decimoDiaHabilMesSiguiente($fechaSello);
        }

        if (in_array($codigo, self::TRES_MESES, true)) {
            return $fechaSello->copy()->addMonthsNoOverflow(3)->endOfDay();
        }

        throw new \DomainException("El tipo de DTE {$codigo} no admite invalidación por este flujo.");
    }

    public function vigente(dte $dte, ?CarbonInterface $ahora = null): bool
    {
        $ahora = $ahora ? Carbon::instance($ahora) : now();

        return $ahora->lte($this->limite($dte))
            && $this->fechaEvento($dte, $ahora)->lte($this->limiteFechaEvento($dte));
    }

    /**
     * Fecha que debe enviarse en identificacion.fecEmi del evento.
     *
     * CCFE/NCE y demás documentos de diez días deben repetir exactamente la
     * fecha de generación del DTE. FE/FEXE/FSEE pueden usar una fecha hasta
     * tres meses posterior a la generación.
     */
    public function fechaEvento(dte $dte, ?CarbonInterface $ahora = null): Carbon
    {
        $generacion = Carbon::parse($dte->fecEmi)->startOfDay();
        $codigo = $this->codigoTipo($dte);

        if (in_array($codigo, self::DIEZ_DIAS_HABILES, true)) {
            return $generacion;
        }

        if (in_array($codigo, self::TRES_MESES, true)) {
            $fecha = $ahora ? Carbon::instance($ahora)->startOfDay() : now()->startOfDay();

            if ($fecha->gt($this->limiteFechaEvento($dte))) {
                throw new \DomainException(
                    'La Fecha del Evento excede los tres meses permitidos desde la Fecha de Generación del DTE.'
                );
            }

            return $fecha;
        }

        throw new \DomainException("El tipo de DTE {$codigo} no admite invalidación por este flujo.");
    }

    public function limiteFechaEvento(dte $dte): Carbon
    {
        $generacion = Carbon::parse($dte->fecEmi)->startOfDay();
        $codigo = $this->codigoTipo($dte);

        if (in_array($codigo, self::DIEZ_DIAS_HABILES, true)) {
            return $generacion->endOfDay();
        }

        if (in_array($codigo, self::TRES_MESES, true)) {
            return $generacion->addMonthsNoOverflow(3)->endOfDay();
        }

        throw new \DomainException("El tipo de DTE {$codigo} no admite invalidación por este flujo.");
    }

    public function mensajeVencimiento(dte $dte): string
    {
        return 'El plazo normativo para invalidar este DTE venció el '
            . $this->limite($dte)->format('d/m/Y') . ' a las 23:59:59.';
    }

    /** Codigo CAT-002 de dos digitos del DTE (relacion tipoDte o id historico). */
    public function codigoTipo(dte $dte): string
    {
        // dtes.tipoDte es columna y relacion a la vez: $dte->tipoDte devuelve
        // siempre el entero, nunca el modelo. Hay que pedir la relacion, y
        // respetar la que ya venga cargada para no repetir la consulta.
        $relacion = $dte->relationLoaded('tipoDte')
            ? $dte->getRelation('tipoDte')
            : $dte->tipoDte()->first();

        $codigo = optional($relacion)->codigo;

        if ($codigo !== null && $codigo !== '') {
            return str_pad((string) $codigo, 2, '0', STR_PAD_LEFT);
        }

        // Respaldo por id historico cuando el catalogo perdio el codigo.
        return match ((int) $dte->tipoDte) {
            1 => '01',
            2 => '03',
            4 => '05',
            10 => '14',
            default => str_pad((string) $dte->tipoDte, 2, '0', STR_PAD_LEFT),
        };
    }

    private function decimoDiaHabilMesSiguiente(CarbonInterface $fechaSello): Carbon
    {
        $fecha = Carbon::instance($fechaSello)->startOfMonth()->addMonth();
        $habiles = 0;

        while ($habiles < 10) {
            if ($this->esDiaHabil($fecha)) {
                $habiles++;
            }

            if ($habiles < 10) {
                $fecha->addDay();
            }
        }

        return $fecha->endOfDay();
    }

    private function esDiaHabil(CarbonInterface $fecha): bool
    {
        if ($fecha->isWeekend()) {
            return false;
        }

        $feriados = array_merge(
            (array) config('dte.feriados_adicionales', []),
            $this->feriadosNacionales((int) $fecha->year)
        );

        return !in_array($fecha->format('Y-m-d'), $feriados, true);
    }

    private function feriadosNacionales(int $year): array
    {
        $fijos = array_map(
            static fn (string $dia): string => $year . '-' . $dia,
            (array) config('dte.feriados_fijos', [])
        );

        $pascua = $this->domingoPascua($year);

        return array_merge($fijos, [
            $pascua->copy()->subDays(3)->toDateString(), // Jueves Santo
            $pascua->copy()->subDays(2)->toDateString(), // Viernes Santo
        ]);
    }

    /** Algoritmo gregoriano de Meeus/Jones/Butcher. */
    private function domingoPascua(int $year): Carbon
    {
        $a = $year % 19;
        $b = intdiv($year, 100);
        $c = $year % 100;
        $d = intdiv($b, 4);
        $e = $b % 4;
        $f = intdiv($b + 8, 25);
        $g = intdiv($b - $f + 1, 3);
        $h = (19 * $a + $b - $d - $g + 15) % 30;
        $i = intdiv($c, 4);
        $k = $c % 4;
        $l = (32 + 2 * $e + 2 * $i - $h - $k) % 7;
        $m = intdiv($a + 11 * $h + 22 * $l, 451);
        $month = intdiv($h + $l - 7 * $m + 114, 31);
        $day = (($h + $l - 7 * $m + 114) % 31) + 1;

        return Carbon::create($year, $month, $day)->startOfDay();
    }
}
