<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Guarda los JSON Schemas publicados por el Ministerio de Hacienda.
 *
 * Hoy la version de cada DTE vive hardcodeada en GeneradorDteV2Service::VERSIONES
 * y en los traits de recepcion. Esta tabla la convierte en dato: al subir el ZIP
 * de esquemas se lee de cada archivo su "identificacion.version.const", asi que
 * nadie tiene que digitarla.
 *
 * Se conserva el historial de versiones; la que se usa es la marcada 'Activo'
 * para cada tipoDte.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('esquema_dtes', function (Blueprint $table) {
            $table->id();

            // Codigo del CAT-002: '01' factura, '03' CCF, '05' nota de credito,
            // '14' sujeto excluido. Los eventos (invalidacion, contingencia) no
            // tienen codigo de CAT-002 y usan las claves 'INV' y 'CON'.
            $table->string('tipoDte', 5);

            $table->string('nombre', 150);
            $table->unsignedSmallInteger('version');

            // Nombre original del archivo dentro del ZIP (ej: fe-ccf-v4.json).
            $table->string('archivo', 150);

            // Ruta relativa en storage donde quedo guardado el esquema.
            $table->string('ruta', 255);

            // sha256 del contenido, para detectar que Hacienda publico un cambio
            // aunque conserve el mismo nombre y version.
            $table->string('hash', 64);

            $table->date('publicado')->nullable();

            $table->enum('status', ['Activo', 'Desactivado'])->default('Desactivado');

            $table->softDeletes();
            $table->timestamps();

            // Un mismo tipo puede tener varias versiones guardadas, pero no la
            // misma version dos veces.
            $table->unique(['tipoDte', 'version'], 'esquema_dtes_tipo_version_unique');
            $table->index(['tipoDte', 'status'], 'esquema_dtes_tipo_status_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('esquema_dtes');
    }
};
