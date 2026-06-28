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
        Schema::create('lotedtes', function (Blueprint $table) {
            $table->id();
            $table->integer('numero');
            $table->date('fecha');
            $table->time('hora');
            $table->unsignedBigInteger('ambiente');
            $table->foreign('ambiente')->references('id')->on('ambiente_destinos');
            $table->string('idEnvio', 255);
            $table->integer('version');
            $table->unsignedBigInteger('sucursal');
            $table->foreign('sucursal')->references('id')->on('sucursales');
            $table->unsignedBigInteger('empresa');
            $table->foreign('empresa')->references('id')->on('empresas');
            $table->string('estado', 50)->nullable();
            $table->string('fhProcesamiento', 255)->nullable();
            $table->string('codigoLote', 255)->nullable();
            $table->string('codigoMsg', 255)->nullable();
            $table->string('descripcionMsg', 255)->nullable();
            $table->json('json')->nullable();
            $table->json('jsonRespuesta')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lotedtes');
    }
};
