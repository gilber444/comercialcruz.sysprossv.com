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
        Schema::create('compras_detalles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('compra');
            $table->foreign('compra')->references('id')->on('compras');
            $table->unsignedBigInteger('producto');
            $table->foreign('producto')->references('id')->on('productos');
            $table->unsignedBigInteger('medida');
            $table->foreign('medida')->references('id')->on('medidas');
            $table->decimal('cantidad', 10,2);
            $table->decimal('ingreso', 10,2);
            $table->decimal('costo', 10,4);
            $table->decimal('total', 10, 4);
            $table->date('fechaVencimiento')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('compras_detalles');
    }
};
