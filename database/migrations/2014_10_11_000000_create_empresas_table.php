<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('empresas', function (Blueprint $table) {
            $table->id();
            $table->string('empresa', 50);
            $table->string('razon', 50);
            $table->string('direccion', 150);
            $table->string('telefono', 20)->nullable();
            $table->string('responsable', 50)->nullable();
            $table->string('registro', 20)->nullable();
            $table->string('giro', 100)->nullable();
            $table->string('nit', 20)->nullable();
            $table->string('tipoContribuyente', 50)->nullable();
            $table->unsignedBigInteger('actividad');
            $table->foreign('actividad')->references('id')->on('actividad_economicas');
            $table->string('desActividad', 255);
            $table->string('correo');
            $table->string('apiPassword', 255);
            $table->unsignedBigInteger('departamento');
            $table->foreign('departamento')->references('id')->on('departamentos');
            $table->unsignedBigInteger('municipio');
            $table->foreign('municipio')->references('id')->on('municipios');
            $table->unsignedBigInteger('distrito');
            $table->foreign('distrito')->references('id')->on('distritos');
            $table->integer('plan')->nullable();
            $table->string('image', 100)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('empresas');
    }
};
