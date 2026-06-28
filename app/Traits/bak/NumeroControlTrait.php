<?php

namespace App\Traits;

use App\Models\ControlNumeracion;
use App\Models\dte;
use App\Models\Empresas;
use App\Models\Parametros;
use App\Models\Sucursales;
use App\Models\Ventas;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

trait NumeroControlTrait
{
    public function obtenerCorrelativoVenta($facturador)
    {
        $parametro = Parametros::find(1);

        if ($parametro) {
            switch ($facturador) {
                case 1:
                    $correlativo = $parametro->tcorrelativo;
                    $parametro->tcorrelativo = $correlativo + 1;
                    break;
                case 2:
                    $correlativo = $parametro->concorrelativo;
                    $parametro->concorrelativo = $correlativo + 1;
                    break;
                case 3:
                    $correlativo = $parametro->crecorrelativo;
                    $parametro->crecorrelativo = $correlativo + 1;
                    break;
                case 4:
                    $correlativo = $parametro->nccorrelativo;
                    $parametro->nccorrelativo = $correlativo + 1;
                    break;
                case 5:
                    $correlativo = $parametro->ndcorrelativo;
                    $parametro->ndcorrelativo = $correlativo + 1;
                    break;
                case 6:
                    $correlativo = $parametro->cocorrelativo;
                    $parametro->cocorrelativo = $correlativo + 1;
                    break;
                default:
                    return 1;
            }
            //$parametro->save();
            return $correlativo ?: 1;
        }
        return 1;
    }

    /*public function obtenerCodigoDTE($facturador)
    {
        $intentos = 0;

        inicio:
        try {
            DB::beginTransaction();

            $sucursal = Sucursales::find(session('sucursal'));
            $parametros = Parametros::find(session('caja'));

            $ano = date('Y');
            $primeros4DigitosEsperados = substr($ano, 0, 4);

            $ultimoCodigo = dte::select('numeroControl')->where('tipoDte', $facturador)
                ->where('empresa', $sucursal->empresa)
                ->where('sucursal', $sucursal->id)
                ->lockForUpdate()
                ->latest('id')
                ->first();

            if ($ultimoCodigo) {
                $ultimos15Digitos = substr($ultimoCodigo->numeroControl, -15);
                $primeros4Digitos = substr($ultimos15Digitos, 0, 4);

                if ($primeros4Digitos == $primeros4DigitosEsperados) {
                    $restantes11Digitos = substr($ultimos15Digitos, 4);
                    $restantes11Digitos = str_pad($restantes11Digitos + 1, 11, '0', STR_PAD_LEFT);
                    $codigo = $primeros4Digitos . $restantes11Digitos;
                } else {
                    $codigo = $primeros4DigitosEsperados . '00000000001';
                }
            } else {
                $codigo = $primeros4DigitosEsperados . '00000000001';
            }

            $cod = ($facturador == 2) ? '03' : '01';

            $nuevoCodigo = 'DTE-' . $cod . '-SUC0' . $sucursal->numero . 'C' . $parametros->caja . '-' . $codigo;

            DB::commit();

            return $nuevoCodigo;

        } catch (\Illuminate\Database\QueryException $e) {
            DB::rollBack();

            if (str_contains($e->getMessage(), 'Deadlock') && $intentos < 3) {
                $intentos++;
                usleep(150000); // espera 150ms antes de intentar otra vez
                goto inicio;
            }

            throw $e;
        }
    }*/

    private function nextCorrelativo(
        int $empresaId,
        int $sucursalId,
        int $tipoDte,
        int $ambiente,
        int $anio
    ): string {

        // OJO: esto requiere que ya estés dentro de una transacción
        $registro = ControlNumeracion::where('empresa', $empresaId)
            ->where('sucursal', $sucursalId)
            ->where('tipoDte', $tipoDte)
            ->where('ambiente', $ambiente)
            ->where('anio', $anio)
            ->lockForUpdate()
            ->first();

        if ($registro) {
            $nuevo = (int) $registro->correlativo + 1;
            $registro->correlativo = $nuevo;
            $registro->save();

            return str_pad($nuevo, 11, '0', STR_PAD_LEFT);
        }

        // Inicializar leyendo último DTE (si aplica)
        $ultimo = dte::select('numeroControl')
            ->where('tipoDte', $tipoDte)
            ->where('empresa', $empresaId)
            ->where('sucursal', $sucursalId)
            ->where('ambiente', $ambiente)
            ->latest('id')
            ->first();

        $nuevo = 1;

        if ($ultimo && $ultimo->numeroControl) {
            $ultimos15  = substr($ultimo->numeroControl, -15);
            $anioUltimo = (int) substr($ultimos15, 0, 4);
            $numero     = (int) substr($ultimos15, 4);
            $nuevo = ($anioUltimo === $anio) ? ($numero + 1) : 1;
        }

        ControlNumeracion::create([
            'empresa'     => $empresaId,
            'sucursal'    => $sucursalId,
            'tipoDte'     => $tipoDte,
            'ambiente'    => $ambiente,
            'anio'        => $anio,
            'correlativo' => $nuevo,
        ]);

        return str_pad($nuevo, 11, '0', STR_PAD_LEFT);
    }

    public function obtenerCodigoDTE($facturador)
    {
        $sucursalId = (int) session('sucursal');
        $cajaId     = (int) session('caja');

        $sucursal   = Sucursales::findOrFail($sucursalId);
        $parametros = Parametros::findOrFail($cajaId);

        $empresaId = (int) $sucursal->empresa;
        $empresa   = Empresas::findOrFail($empresaId);

        $ambiente = (int) ($empresa->ambiente ?? 1);
        $anio     = (int) date('Y');

        $correlativo11 = $this->nextCorrelativo(
            $empresaId,
            (int) $sucursal->id,
            (int) $facturador,
            $ambiente,
            $anio
        );

        $codigoCompleto = $anio . $correlativo11;

        // Determinar el código de tipo de DTE
        $codTipo = ($facturador == 2) ? '03' : (($facturador == 4) ? '05' : '01');

        // Formato correcto: 4 dígitos sucursal + 4 dígitos punto de venta
        $codEstableMH    = strtoupper(trim($sucursal->codEstableMH ?? 'M001'));
        $codPuntoVentaMH = strtoupper(trim($parametros->codPuntoVentaMH ?? 'P001'));

        // Pad a 3 dígitos y forma "S001P001"
        $bloqueSP = $codEstableMH . $codPuntoVentaMH; // Ej: S001P001

        $nuevoCodigo = 'DTE-' . $codTipo . '-' . $bloqueSP . '-' . $codigoCompleto;

        return $nuevoCodigo;
    }

    public function obtenerCodigoDTENota($facturador, $caja)
    {
        $parametros = Parametros::findOrFail($caja);

        $sucursalId = (int) (session('sucursal') ?? $parametros->sucursal);
        $sucursal   = Sucursales::findOrFail($sucursalId);

        $empresaId = (int) $sucursal->empresa;
        $empresa   = Empresas::findOrFail($empresaId);

        $ambiente = (int) ($empresa->ambiente ?? 1);
        $anio     = (int) date('Y');

        // ⬅️ año real, no '0000'
        $primeros4 = (string) $anio;

        // correlativo controlado por empresa+sucursal+tipoDte+ambiente+anio
        $correlativo11 = $this->nextCorrelativo(
            $empresaId,
            (int) $sucursal->id,
            (int) $facturador,
            $ambiente,
            $anio
        );

        $codigoCompleto = $primeros4 . $correlativo11;

        // Mapping de tipo (deja tu lógica)
        $codTipo = ($facturador == 1) ? '01'
            : (($facturador == 2) ? '03'
                : (($facturador == 5) ? '05' : '00'));

        // Formato MH
        $codEstableMH    = strtoupper(trim($sucursal->codEstableMH ?? 'M001'));
        $codPuntoVentaMH = strtoupper(trim($parametros->codPuntoVentaMH ?? 'P001'));
        $bloqueSP = $codEstableMH . $codPuntoVentaMH;

        return 'DTE-' . $codTipo . '-' . $bloqueSP . '-' . $codigoCompleto;
    }
}
