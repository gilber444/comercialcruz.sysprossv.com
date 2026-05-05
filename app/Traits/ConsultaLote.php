<?php
namespace App\Traits;

use App\Models\dte;
use App\Models\lotedte;
use App\Models\lotedteDetalles;
use App\Traits\RecepcionDTEC;

trait ConsultaLote
{
    use GenerarToken;
    use RecepcionDTEC;
    use RecepcionDTEF;

    public function ConsultaLote($id)
    {
        $lote = lotedte::find($id);

        $detalle = lotedteDetalles::where('lote', $lote->id)->get();
        foreach ($detalle as $deta) {
            $dte = dte::find($deta->dte);

            if ($dte->tipoDte == 1) {
                $this->RecepcionDTEF($deta->dte);
            } else {
                $this->RecepcionDTEC($deta->dte);
            }
        }
    }
}
