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
        Schema::create('sucursales', function (Blueprint $table) {
            $table->id();
            $table->integer('numero');
            $table->string('nombre');
            $table->string('direccion');
            $table->string('telefono', 15)->nullable();
            $table->integer('cajas');
            $table->unsignedBigInteger('empresa');
            $table->foreign('empresa')->references('id')->on('empresas');
            $table->unsignedBigInteger('tipo');
            $table->foreign('tipo')->references('id')->on('tipo_establecimientos');
            $table->unsignedBigInteger('departamento');
            $table->foreign('departamento')->references('id')->on('departamentos');
            $table->unsignedBigInteger('municipio');
            $table->foreign('municipio')->references('id')->on('municipios');
            $table->unsignedBigInteger('distrito');
            $table->foreign('distrito')->references('id')->on('distritos');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sucursales');
    }
};
