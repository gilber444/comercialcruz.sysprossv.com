<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Carrito temporal del Evento de Retorno, por usuario.
 * Mismo rol que temp_nota_creditos en la Nota de Credito.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('temp_evento_retornos', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('usuario');
            $table->foreign('usuario')->references('id')->on('users');

            $table->unsignedBigInteger('venta');
            $table->foreign('venta')->references('id')->on('ventas');

            $table->unsignedBigInteger('ventaDetalle');
            $table->foreign('ventaDetalle')->references('id')->on('ventas_detalles');

            $table->unsignedBigInteger('producto')->nullable();
            $table->foreign('producto')->references('id')->on('productos');

            $table->string('name', 255);
            $table->string('unidad', 50)->nullable();
            $table->unsignedInteger('uniMedida')->default(59);

            // cantidad = lo que se devuelve; vendida = lo que traia la factura,
            // para no permitir devolver de mas.
            $table->decimal('cantidad', 10, 2);
            $table->decimal('vendida', 10, 2);
            $table->decimal('descargar', 10, 2)->default(0);

            $table->decimal('precio', 10, 4)->default(0);
            $table->decimal('descuento', 10, 4)->default(0);
            $table->decimal('iva', 10, 4)->default(0);
            $table->decimal('total', 10, 4)->default(0);

            $table->timestamps();

            $table->index('usuario', 'temp_evento_retornos_usuario_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('temp_evento_retornos');
    }
};
