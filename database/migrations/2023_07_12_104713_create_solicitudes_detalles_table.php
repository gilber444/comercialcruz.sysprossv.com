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
        Schema::create('solicitudes_detalles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('solicitud');
            $table->foreign('solicitud')->references('id')->on('solicitudes');
            $table->unsignedBigInteger('producto');
            $table->foreign('producto')->references('id')->on('productos');
            $table->unsignedBigInteger('medida');
            $table->foreign('medida')->references('id')->on('medidas');
            $table->unsignedBigInteger('origen');
            $table->foreign('origen')->references('id')->on('sucursales');
            $table->unsignedBigInteger('destino');
            $table->foreign('destino')->references('id')->on('sucursales');
            $table->decimal('cantidad', 10, 2);
            $table->decimal('contenedor', 10, 2);
            $table->decimal('descargar', 10, 2);
            $table->decimal('costo', 10, 4);
            $table->decimal('total', 10, 4);
            $table->unsignedBigInteger('realizado');
            $table->foreign('realizado')->references('id')->on('users');
            $table->unsignedBigInteger('autorizado');
            $table->foreign('autorizado')->references('id')->on('users');
            $table->unsignedBigInteger('despachado');
            $table->foreign('despachado')->references('id')->on('users');
            $table->unsignedBigInteger('ingresado');
            $table->foreign('ingresado')->references('id')->on('users');
            $table->string('estado', 50);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('solicitudes_detalles');
    }
};
