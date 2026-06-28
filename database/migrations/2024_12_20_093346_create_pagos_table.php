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
        Schema::create('pagos', function (Blueprint $table) {
            $table->id();
            $table->integer('correlativo');
            $table->date('fecha');
            $table->time('hora');
            $table->unsignedBigInteger('user');
            $table->foreign('user')->references('id')->on('users');
            $table->longText('concepto');
            $table->decimal('total', 15, 4);
            $table->unsignedBigInteger('cuenta_pagar');
            $table->foreign('cuenta_pagar')->references('id')->on('cuentas_pagars');
            $table->unsignedBigInteger('tipo_pago');
            $table->foreign('tipo_pago')->references('id')->on('tipo_pagos');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pagos');
    }
};
