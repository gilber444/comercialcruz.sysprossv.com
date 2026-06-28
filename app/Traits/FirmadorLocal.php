<?php

namespace App\Traits;

use App\Models\dte;
use App\Models\Firmador;
use App\Traits\FirmadorTrait;

trait FirmadorLocal
{
    use FirmadorTrait;

    /**
     * Firma un DTE localmente y actualiza su estado a "Firmado".
     *
     * @param int|string $id ID del DTE a firmar.
     * @param string $json JSON original del DTE.
     * @return string Firma JWT generada.
     * @throws \Exception Si el DTE no existe, el JSON es inválido o la firma falla.
     */
    public function FirmadorLocal($id, $json)
    {
        $dte = dte::find($id);

        if (!$dte) {
            throw new \Exception("No se encontró el DTE con ID {$id} para firmar.");
        }

        $contenidoJSON = json_decode($json, true);

        if (!is_array($contenidoJSON)) {
            throw new \Exception("El JSON del DTE {$id} no es válido.");
        }

        $resultado = $this->signJson($contenidoJSON);

        if (!$resultado || !is_string($resultado)) {
            throw new \Exception("La firma del DTE {$id} devolvió un resultado vacío.");
        }

        // Un JWT debe tener exactamente 3 partes separadas por puntos
        if (substr_count($resultado, '.') !== 2) {
            throw new \Exception("La firma del DTE {$id} no es válida: " . $resultado);
        }

        // Guardar la firma en la tabla 'firmador'
        $firma = new Firmador();
        $firma->firmador = $resultado;
        $firma->fecha = date('Y-m-d');
        $firma->dte = $id;
        $firma->json = json_encode($contenidoJSON);
        $firma->estado = 'Firmado';
        $firma->save();

        // Preparar el JSON actualizado
        $contenidoJSON['documento'] = $resultado;
        $jsonActualizado = json_encode($contenidoJSON);

        // Actualizar el modelo DTE
        $dte->estado = 'Firmado';
        $dte->jsonDte = $jsonActualizado;
        $dte->save();

        return $resultado;
    }
}
