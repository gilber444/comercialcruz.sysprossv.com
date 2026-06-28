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
        Schema::create('cotizaciones', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cliente');
            $table->foreign('cliente')->references('id')->on('clientes');
            $table->unsignedBigInteger('tipoPago');
            $table->foreign('tipoPago')->references('id')->on('tipo_pagos');
            $table->unsignedBigInteger('facturador');
            $table->foreign('facturador')->references('id')->on('facturadores');
            $table->unsignedBigInteger('tipoDocumento');
            $table->foreign('tipoDocumento')->references('id')->on('tipo_documentos');
            $table->integer('correlativo');
            $table->date('fecha');
            $table->time('hora');
            $table->date('fechaPago');
            $table->enum('tipo', ['Fisico', 'DTE'])->default('Fisico');
            $table->string('codigo', 255)->nullable();
            $table->string('numero', 255)->nullable();
            $table->string('sello', 255)->nullable();
            $table->unsignedBigInteger('vendedor');
            $table->foreign('vendedor')->references('id')->on('users');
            $table->unsignedBigInteger('caja');
            $table->foreign('caja')->references('id')->on('parametros');
            $table->unsignedBigInteger('sucursal');
            $table->foreign('sucursal')->references('id')->on('sucursales');
            $table->unsignedBigInteger('empresa');
            $table->foreign('empresa')->references('id')->on('empresas');
            $table->decimal('subtotal', 10, 4)->nullable();
            $table->decimal('descuento', 10, 4)->nullable();
            $table->decimal('iva', 10,4)->nullable();
            $table->decimal('percepcion', 10,4)->nullable();
            $table->decimal('total', 10, 4)->nullable();
            $table->string('estado', 50);
            $table->string('observaciones', 255)->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cotizaciones');
    }
};
