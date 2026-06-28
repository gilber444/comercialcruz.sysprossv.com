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
        Schema::create('dtes', function (Blueprint $table) {
            $table->id();
            $table->string('motivoContin', 250)->nullable();
            $table->string('version', 2);
            $table->unsignedBigInteger('ambiente');
            $table->foreign('ambiente')->references('id')->on('ambiente_destinos');
            $table->unsignedBigInteger('tipoDte');
            $table->foreign('tipoDte')->references('id')->on('tipo_documentos');
            $table->string('numeroControl');
            $table->string('codigoGeneracion');
            $table->unsignedBigInteger('tipoModelo');
            $table->foreign('tipoModelo')->references('id')->on('modelo_facturacions');
            $table->unsignedBigInteger('tipoOperacion');
            $table->foreign('tipoOperacion')->references('id')->on('tipo_transmisions');
            $table->unsignedBigInteger('tipoContingencia');
            $table->foreign('tipoContingencia')->references('id')->on('tipo_contigencias');
            $table->date('fecEmi');
            $table->time('horEmi');
            $table->string('tipoMoneda', 3);
            $table->string('documentoRelacionado', 255)->nullable();
            $table->unsignedBigInteger('emisor');
            $table->foreign('emisor')->references('id')->on('sucursales');
            $table->unsignedBigInteger('receptor');
            $table->foreign('receptor')->references('id')->on('clientes');
            $table->string('otrosDocuentos', 255)->nullable();
            $table->string('ventaTercero', 255)->nullable();
            $table->unsignedBigInteger('venta');
            $table->foreign('venta')->references('id')->on('ventas');
            $table->unsignedBigInteger('tocken');
            $table->foreign('tocken')->references('id')->on('tockens');
            $table->string('sello', 255)->nullable();
            $table->string('estado', 100)->nullable();
            $table->json('jsonDte')->nullable();
            $table->unsignedBigInteger('caja');
            $table->foreign('caja')->references('id')->on('parametros');
            $table->unsignedBigInteger('sucursal');
            $table->foreign('sucursal')->references('id')->on('sucursales');
            $table->unsignedBigInteger('empresa');
            $table->foreign('empresa')->references('id')->on('empresas');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dtes');
    }
};
