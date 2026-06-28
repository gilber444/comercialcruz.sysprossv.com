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
        Schema::create('creditos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('venta');
            $table->foreign('venta')->references('id')->on('ventas');
            $table->unsignedBigInteger('cliente');
            $table->foreign('cliente')->references('id')->on('clientes');
            $table->unsignedBigInteger('empresa');
            $table->foreign('empresa')->references('id')->on('empresas');
            $table->unsignedBigInteger('sucursal');
            $table->foreign('sucursal')->references('id')->on('sucursales');
            $table->integer('correlativo');
            $table->date('fechaCredito');
            $table->date('fechaPago')->nullable();
            $table->decimal('total', 10, 4);
            $table->decimal('saldo', 10, 4);
            $table->enum('estado', ['Pendiente', 'Pagado', 'Cancelado'])->default('Pendiente');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('creditos');
    }
};
