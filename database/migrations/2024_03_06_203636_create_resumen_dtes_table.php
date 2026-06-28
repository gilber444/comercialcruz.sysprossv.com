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
        Schema::create('resumen_dtes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('dte');
            $table->foreign('dte')->references('id')->on('dtes');
            $table->decimal('totalNoSuj', 10, 2);
            $table->decimal('totalExenta', 10, 2);
            $table->decimal('totalGravada', 10, 2);
            $table->decimal('totalIva', 10,2);
            $table->decimal('subTotalVentas', 10, 2);
            $table->decimal('descuNoSuj', 10, 2);
            $table->decimal('descuExenta', 10, 2);
            $table->decimal('descuGravada', 10, 2);
            $table->decimal('porcentajeDescuento', 10, 2);
            $table->decimal('totalDescu', 10, 2);
            $table->unsignedBigInteger('tributo');
            $table->foreign('tributo')->references('id')->on('tributos');
            $table->string('codigo', 3)->nullable();
            $table->string('descripcion', 255)->nullable();
            $table->decimal('valor', 10, 2)->nullable();
            $table->decimal('subTotal', 10, 2);
            $table->decimal('ivaPerci1', 10, 2);
            $table->decimal('ivaRete1', 10, 2);
            $table->decimal('reteRenta', 10, 2);
            $table->decimal('montoTotalOperacion', 10, 2);
            $table->decimal('totalNoGravado', 10, 2);
            $table->decimal('totalPagar', 10, 2);
            $table->string('totalLetras', 255);
            $table->decimal('saldoFavor', 10, 2);
            $table->unsignedBigInteger('condicionOperacion');
            $table->foreign('condicionOperacion')->references('id')->on('condicion_operacions');
            $table->unsignedBigInteger('pagos');
            $table->foreign('pagos')->references('id')->on('tipo_pagos');
            $table->decimal('montoPagado', 10,2)->nullable();
            $table->string('refencia', 255)->nullable();
            $table->string('palzo', 255)->nullable();
            $table->string('periodo', 255)->nullable();
            $table->string('numPagoElectronico', 255)->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('resumen_dtes');
    }
};
