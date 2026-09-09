<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('personalizaciones_dte', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->unique()->constrained('empresas')->cascadeOnDelete();
            $table->string('color_primario', 7)->default('#33475B');
            $table->string('color_encabezado', 7)->default('#EEF1F5');
            $table->string('color_texto', 7)->default('#1F2937');
            $table->string('color_texto_suave', 7)->default('#64748B');
            $table->string('color_borde', 7)->default('#DFE4EA');
            $table->string('color_exito', 7)->default('#1F7A43');
            $table->string('color_peligro', 7)->default('#B02A37');
            $table->string('color_fondo', 7)->default('#FFFFFF');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personalizaciones_dte');
    }
};
