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
        Schema::create('contingencia_dtes', function (Blueprint $table) {
            $table->id();
            $table->integer('numero');
            $table->integer('version');
            $table->unsignedBigInteger('ambiente');
            $table->foreign('ambiente')->references('id')->on('ambiente_destinos');
            $table->string('codigoGeneracion', 255);
            $table->date('fTransmision');
            $table->time('hTransmision');
            $table->unsignedBigInteger('emisor');
            $table->foreign('emisor')->references('id')->on('sucursales');
            $table->unsignedBigInteger('empresa');
            $table->foreign('empresa')->references('id')->on('empresas');
            $table->date('fInicio');
            $table->date('fFin');
            $table->time('hInicio');
            $table->time('hFin');
            $table->unsignedBigInteger('tipoContingencia');
            $table->foreign('tipoContingencia')->references('id')->on('tipo_contigencias');
            $table->longText('motivoContingencia')->nullable();
            $table->json('json')->nullable();
            $table->longText('jsonFirmado')->nullable();
            $table->string('estado', 50);
            $table->string('fechaHora', 100)->nullable();
            $table->string('mensaje', 200)->nullable();
            $table->string('selloRecibido', 255)->nullable();
            $table->longText('observaciones')->nullable();
            $table->longText('jsonRespuesta')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contingencia_dtes');
    }
};
