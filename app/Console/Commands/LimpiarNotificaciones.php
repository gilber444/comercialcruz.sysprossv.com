<?php

namespace App\Console\Commands;

use App\Models\Notificacion;
use Illuminate\Console\Command;

class LimpiarNotificaciones extends Command
{
    protected $signature   = 'notificaciones:limpiar';
    protected $description = 'Marca como leídas las notificaciones del día anterior y elimina las de más de 30 días';

    public function handle()
    {
        // 1. Marcar como leídas todas las no leídas de días anteriores (no del día de hoy)
        $marcadas = Notificacion::where('leido', false)
            ->whereDate('created_at', '<', now()->toDateString())
            ->update(['leido' => true]);

        $this->info("Notificaciones marcadas como leídas: {$marcadas}");

        // 2. Eliminar permanentemente las que tienen más de 30 días (incluyendo soft-deleted)
        $eliminadas = Notificacion::withTrashed()
            ->where('created_at', '<', now()->subDays(30))
            ->forceDelete();

        $this->info("Notificaciones eliminadas permanentemente: {$eliminadas}");
    }
}
