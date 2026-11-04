<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

/**
 * Guarda el JSON del esquema en la misma fila de esquema_dtes.
 *
 * El archivo en storage solo existe en el equipo donde se subio el ZIP (el VPS);
 * con el contenido en la tabla, las cajas lo reciben con esquemas:bajar-desde-vps
 * y validan igual que el VPS.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('esquema_dtes', 'contenido')) {
            Schema::table('esquema_dtes', function (Blueprint $table) {
                $table->longText('contenido')->nullable()->after('hash');
            });
        }

        // Llena el contenido de los esquemas ya cargados con su archivo, si esta
        // en este equipo y coincide con el hash guardado.
        foreach (DB::table('esquema_dtes')->whereNull('contenido')->get(['id', 'ruta', 'hash']) as $fila) {
            try {
                if (!$fila->ruta || !Storage::exists($fila->ruta)) {
                    continue;
                }
                $contenido = Storage::get($fila->ruta);
                if (hash('sha256', $contenido) === $fila->hash) {
                    DB::table('esquema_dtes')->where('id', $fila->id)->update(['contenido' => $contenido]);
                }
            } catch (\Throwable $e) {
                // Sin archivo legible queda en null; el validador sigue usando storage
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('esquema_dtes', 'contenido')) {
            Schema::table('esquema_dtes', function (Blueprint $table) {
                $table->dropColumn('contenido');
            });
        }
    }
};
