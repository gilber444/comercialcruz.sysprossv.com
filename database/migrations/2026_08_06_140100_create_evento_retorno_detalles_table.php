<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cuerpo del Evento de Retorno: los items que el cliente devuelve.
 *
 * Se guarda "ventaDetalle" para saber de que linea de la factura sale cada item
 * y poder validar que no se devuelva mas cantidad de la vendida, sumando lo ya
 * retornado en eventos anteriores sobre la misma factura.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evento_retorno_detalles', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('evento');
            $table->foreign('evento')->references('id')->on('evento_retornos')->cascadeOnDelete();

            $table->unsignedInteger('numItem');
            $table->unsignedTinyInteger('tipoItem')->default(1);

            $table->unsignedBigInteger('producto')->nullable();
            $table->foreign('producto')->references('id')->on('productos');

            $table->unsignedBigInteger('ventaDetalle')->nullable();
            $table->foreign('ventaDetalle')->references('id')->on('ventas_detalles');

            // codigoGeneracion del DTE al que aplica el item (lo pide el schema).
            $table->string('codigoGeneracion', 36);

            $table->string('codigo', 25)->nullable();
            $table->string('descripcion', 1500);

            $table->decimal('cantidad', 10, 2);
            $table->decimal('descargar', 10, 2)->default(0);
            $table->unsignedInteger('uniMedida')->default(59);

            $table->decimal('precioUni', 10, 4)->default(0);
            $table->decimal('montoDescu', 10, 4)->default(0);
            $table->decimal('ventaNoSuj', 10, 4)->default(0);
            $table->decimal('ventaExenta', 10, 4)->default(0);
            $table->decimal('ventaGravada', 10, 4)->default(0);
            $table->decimal('ivaItem', 10, 4)->default(0);
            $table->decimal('psv', 10, 4)->default(0);
            $table->decimal('noGravado', 10, 4)->default(0);

            $table->timestamps();

            $table->index('evento', 'evento_retorno_detalles_evento_index');
            $table->index('ventaDetalle', 'evento_retorno_detalles_venta_detalle_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evento_retorno_detalles');
    }
};
