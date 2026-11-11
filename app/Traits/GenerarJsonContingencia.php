<?php
namespace App\Traits;

use App\Models\ContingenciaDte;
use App\Models\ContingenciadteDetalles;
use App\Models\Parametros;
use App\Models\Sucursales;
use App\Support\NormalizadorCorreoDte;
use App\Support\VersionEventoDte;

/**
 * Evento de contingencia conforme al schema oficial v4 (Normativa 2.0).
 *
 * El emisor v4 exige codEstableMH y codPuntoVentaMH (4 caracteres o null) y
 * no admite codPuntoVenta: mandar ese campo hace que Hacienda rechace el
 * evento por additionalProperties.
 */
trait GenerarJsonContingencia
{
    public function GenerarJsonContingencia($id)
    {
        $contingencia = ContingenciaDte::with('ambienteDestino')->with(['sucursal' => function($query) {
            $query->with('tipoEstable');
        }])->with('empresas')->with('tipoContin')->find($id);

        if (!$contingencia) {
            throw new \RuntimeException('No se encontro la contingencia ' . $id . '.');
        }

        $detalleContin = ContingenciadteDetalles::with(['dtes' => function($query){ $query->with('RtipoDte');}])->where('contingencia', $contingencia->id)->orderBy('noItem')->get();

        // Una sola fuente para identificacion.version y la columna version.
        $version = VersionEventoDte::contingencia();

        $identificacion = [
            'version' => $version,
            'ambiente' => optional($contingencia->ambienteDestino)->codigo,
            'codigoGeneracion' => strtoupper((string) $contingencia->codigoGeneracion),
            'fTransmision' => $contingencia->fTransmision,
            'hTransmision' => $contingencia->hTransmision,
        ];

        // Codigos MH del establecimiento y punto de venta: el numero de control
        // del primer DTE los trae (DTE-01-M001P001-...); si no, la sucursal y
        // la caja del DTE; y como ultimo respaldo los valores por defecto.
        $primerDte = optional($detalleContin->first())->dtes;
        $codEstableMh = null;
        $codPuntoVentaMh = null;

        if ($primerDte && preg_match('/^DTE-[0-9]{2}-([A-Z0-9]{4})([A-Z0-9]{4})-/', strtoupper((string) $primerDte->numeroControl), $coincidencias)) {
            $codEstableMh = $coincidencias[1];
            $codPuntoVentaMh = $coincidencias[2];
        }

        // contingencia_dtes.emisor guarda el id de la empresa (ContingenciaDTEController
        // lo llena con $user->empresa), asi que la sucursal real del emisor es la
        // del DTE; la relacion "sucursal" queda solo como respaldo.
        $sucursalDte = $primerDte ? Sucursales::with('tipoEstable')->find($primerDte->sucursal) : null;
        $sucursalEmisor = $sucursalDte ?: $contingencia->sucursal;

        if (!$codEstableMh) {
            $codEstableMh = $this->codigoMhContingencia(optional($sucursalDte)->codEstableMH)
                ?? $this->codigoMhContingencia(optional($contingencia->sucursal)->codEstableMH);
        }

        if (!$codPuntoVentaMh && $primerDte) {
            $codPuntoVentaMh = $this->codigoMhContingencia(optional(Parametros::find($primerDte->caja))->codPuntoVentaMH);
        }

        $emisor = [
            'nit' => $contingencia->empresas->nit,
            'nombre' => $contingencia->empresas->empresa,
            'nombreResponsable' => $contingencia->empresas->responsable,
            'tipoDocResponsable' => "36",
            'numeroDocResponsable' => $contingencia->empresas->nit,
            'tipoEstablecimiento' => optional(optional($sucursalEmisor)->tipoEstable)->codigo,
            'codEstableMH' => $this->codigoMhContingencia($codEstableMh) ?? 'M001',
            'codPuntoVentaMH' => $this->codigoMhContingencia($codPuntoVentaMh) ?? 'P001',
            'telefono' => (string) (optional($sucursalEmisor)->telefono ?: $contingencia->empresas->telefono),
            'correo' => NormalizadorCorreoDte::normalizar($contingencia->empresas->correo),
        ];

        $detalleDTE = [];
        foreach ($detalleContin as $d) {
            $detalleDTE[] = [
                'noItem' => (int) $d->noItem,
                'codigoGeneracion' => strtoupper((string) $d->dtes->codigoGeneracion),
                'tipoDoc' => str_pad((string) $d->dtes->RtipoDte->codigo, 2, '0', STR_PAD_LEFT),
            ];
        }

        $motivo = [
            'fInicio' => $contingencia->fInicio,
            'fFin' => $contingencia->fFin,
            'hInicio' => $contingencia->hInicio,
            'hFin' => $contingencia->hFin,
            'tipoContingencia' => (int)$contingencia->tipoContin->codigo,
            'motivoContingencia' => $contingencia->motivoContingencia ?: $contingencia->tipoContin->valor,
        ];

        $json = [
            'identificacion' => $identificacion,
            'emisor' => $emisor,
            'detalleDTE' => $detalleDTE,
            'motivo' => $motivo,
        ];

        $contingencia->version = $version;
        $contingencia->json = json_encode($json);
        $contingencia->save();

        return json_encode($json);
    }

    /** Codigo MH de 4 caracteres en mayusculas, o null si no es valido. */
    private function codigoMhContingencia($codigo): ?string
    {
        $codigo = strtoupper(trim((string) $codigo));

        return strlen($codigo) === 4 ? $codigo : null;
    }
}
