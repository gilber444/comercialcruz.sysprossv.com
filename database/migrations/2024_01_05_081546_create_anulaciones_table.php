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
        Schema::create('anulaciones', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('caja');
            $table->foreign('caja')->references('id')->on('parametros');
            $table->unsignedBigInteger('sucursal');
            $table->foreign('sucursal')->references('id')->on('sucursales');
            $table->unsignedBigInteger('empresa');
            $table->foreign('empresa')->references('id')->on('empresas');
            $table->unsignedBigInteger('corte');
            $table->foreign('corte')->references('id')->on('cortes');
            $table->unsignedBigInteger('venta');
            $table->foreign('venta')->references('id')->on('ventas');
            $table->unsignedBigInteger('cajas');
            $table->foreign('cajas')->references('id')->on('cajas');
            $table->unsignedBigInteger('facturador');
            $table->foreign('facturador')->references('id')->on('facturadores');
            $table->unsignedBigInteger('tipoPago');
            $table->foreign('tipoPago')->references('id')->on('tipo_pagos');
            $table->integer('correlativo');
            $table->string('codigo', 255)->nullable();
            $table->string('numero', 255)->nullable();
            $table->string('sello', 255)->nullable();
            $table->date('fecha');
            $table->time('hora');
            $table->unsignedBigInteger('cajero');
            $table->foreign('cajero')->references('id')->on('users');
            $table->unsignedBigInteger('autorizado');
            $table->foreign('autorizado')->references('id')->on('users');
            $table->string('comprobante', 100)->nullable();
            $table->decimal('efectivo', 10,4)->nullable();
            $table->decimal('cambio', 10,4)->nullable();
            $table->decimal('subtotal', 10, 4)->nullable();
            $table->decimal('descuento', 10, 4)->nullable();
            $table->decimal('iva', 10,4)->nullable();
            $table->decimal('total', 10, 4)->nullable();
            $table->string('estado', 50);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('anulaciones');
    }
};
