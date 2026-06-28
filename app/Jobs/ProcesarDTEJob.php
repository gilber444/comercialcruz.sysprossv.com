<?php

namespace App\Jobs;

use App\Models\dte;
use App\Traits\RecepcionDTEF;
use App\Traits\RecepcionDTEC;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcesarDTEJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    use RecepcionDTEF, RecepcionDTEC;

    public int $dteId;

    public function __construct(int $dteId)
    {
        $this->dteId = $dteId;
    }

    public function handle()
    {
        $dte = dte::find($this->dteId);
        if (! $dte) {
            return;
        }

        //Log::info('ProcesarDTEJob INICIADO', ['dte_id' => $this->dteId]);

        //dd($this->dteId);

        // Solo procesa el DTE, sin Livewire, sin imprimir
        //$json = $this->GeneraJsonF($dte->id);
        //$firma = $this->FirmadorDTE($id, $json);
        //$firma = $this->FirmadorLocal($dte->id, $json);
        $this->RecepcionDTEF($this->dteId);


        // Aquí ya queda actualizado estado, sello, jsonDte, etc.
        // (lo hace tu trait RecepcionDTEF/RecepcionDTEC)
        //Log::info('ProcesarDTEJob FINALIZADO', ['dte_id' => $dte->id]);
    }
}
