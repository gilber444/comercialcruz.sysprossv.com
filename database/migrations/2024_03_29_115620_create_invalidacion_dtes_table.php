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
        Schema::create('invalidacion_dtes', function (Blueprint $table) {
            $table->id();
            $table->enum('tipo', ['Devolucion', 'Anulacion']);
            $table->unsignedBigInteger('ambiente');
            $table->foreign('ambiente')->references('id')->on('ambiente_destinos');
            $table->integer('numero');
            $table->string('codigoGeneracion', 255);
            $table->date('fecAnula');
            $table->time('horAnula');
            $table->unsignedBigInteger('emisor');
            $table->foreign('emisor')->references('id')->on('sucursales');
            $table->unsignedBigInteger('dte');
            $table->foreign('dte')->references('id')->on('dtes');
            $table->string('codigoGeneracionR', 255)->nullable();
            $table->unsignedBigInteger('tipoAnulacion');
            $table->foreign('tipoAnulacion')->references('id')->on('tipo_invalidacions');
            $table->string('motivoAnulacion', 255)->nullable();
            $table->unsignedBigInteger('responsable');
            $table->foreign('responsable')->references('id')->on('users');
            $table->unsignedBigInteger('solicita');
            $table->foreign('solicita')->references('id')->on('users');
            $table->unsignedBigInteger('caja');
            $table->foreign('caja')->references('id')->on('parametros');
            $table->unsignedBigInteger('sucursal');
            $table->foreign('sucursal')->references('id')->on('sucursales');
            $table->unsignedBigInteger('empresa');
            $table->foreign('empresa')->references('id')->on('empresas');
            $table->string('estado', 50);
            $table->string('selloRecibido')->nullable();
            $table->string('fhProcesamiento', 255)->nullable();
            $table->string('descripcionMsg', 255)->nullable();
            $table->json('json')->nullable();
            $table->json('jsonRespuesta')->nullable();
            $table->longText('docFirmado')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invalidacion_dtes');
    }
};
