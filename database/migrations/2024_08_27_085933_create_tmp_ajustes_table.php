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
        Schema::create('tmp_ajustes', function (Blueprint $table) {
            $table->id();
            $table->integer('producto');
            $table->string('name');
            $table->decimal('price', 10, 4);
            $table->decimal('quantity', 10, 2);
            $table->integer('sucursal');
            $table->string('codebar');
            $table->integer('unidad');
            $table->string('medida');
            $table->decimal('total', 10, 4);
            $table->decimal('limit', 10, 2);
            $table->integer('ingreso');
            $table->string('inventario');
            $table->integer('usuario');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tmp_ajustes');
    }
};
