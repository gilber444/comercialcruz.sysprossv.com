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
        Schema::create('kardexes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('producto');
            $table->foreign('producto')->references('id')->on('productos');
            $table->unsignedBigInteger('inventario');
            $table->foreign('inventario')->references('id')->on('inventarios');
            $table->string('descripcion', 255);
            $table->date('fecha');
            $table->time('hora');
            $table->decimal('ingresoCantidad', 10, 4);
            $table->decimal('ingresoValor', 10, 4);
            $table->decimal('egresoCantidad', 10, 4);
            $table->decimal('egresoValor', 10, 4);
            $table->decimal('saldoCantidad', 10, 4);
            $table->decimal('saldoValor', 10, 4);
            $table->unsignedBigInteger('sucursal');
            $table->foreign('sucursal')->references('id')->on('sucursales');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kardexes');
    }
};
