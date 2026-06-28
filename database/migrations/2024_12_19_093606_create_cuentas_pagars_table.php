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
        Schema::create('cuentas_pagars', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('compra');
            $table->foreign('compra')->references('id')->on('compras');
            $table->unsignedBigInteger('proveedor');
            $table->foreign('proveedor')->references('id')->on('proveedores');
            $table->decimal('monto_total', 15, 4);
            $table->decimal('saldo', 15, 4);
            $table->enum('estado', ['pendiente', 'parcial', 'pagada'])->default('pendiente');
            $table->date('fecha_vencimiento');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cuentas_pagars');
    }
};
