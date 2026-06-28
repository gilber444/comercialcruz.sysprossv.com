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
        Schema::create('precompra_detalles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('precompra');
            $table->foreign('precompra')->references('id')->on('precompras');
            $table->unsignedBigInteger('producto');
            $table->foreign('producto')->references('id')->on('productos');
            $table->unsignedBigInteger('medida');
            $table->foreign('medida')->references('id')->on('medidas');
            $table->string('tipoItem');
            $table->string('codigo');
            $table->string('descripcion');
            $table->integer('cantidad');
            $table->integer('uniMedida');
            $table->decimal('precioUni', 10, 4);
            $table->decimal('montoDescu', 10, 4);
            $table->decimal('ventaNoSuj', 10, 4);
            $table->decimal('ventaExenta', 10, 4);
            $table->decimal('ventaGravada', 10, 4);
            $table->string('status', 50);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('precompra_detalles');
    }
};
