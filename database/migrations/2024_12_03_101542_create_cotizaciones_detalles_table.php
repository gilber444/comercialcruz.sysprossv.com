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
        Schema::create('cotizaciones_detalles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cotizacion');
            $table->foreign('cotizacion')->references('id')->on('cotizaciones');
            $table->unsignedBigInteger('producto');
            $table->foreign('producto')->references('id')->on('productos');
            $table->unsignedBigInteger('medida');
            $table->foreign('medida')->references('id')->on('medidas');
            $table->string('unidad', 50);
            $table->decimal('descargar', 10,2);
            $table->decimal('cantidad', 10, 2);
            $table->decimal('precio', 10,4);
            $table->decimal('descuento', 10, 4)->nullable();
            $table->decimal('subtotal', 10, 4);
            $table->decimal('iva', 10,4);
            $table->decimal('total', 10, 4);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cotizaciones_detalles');
    }
};
