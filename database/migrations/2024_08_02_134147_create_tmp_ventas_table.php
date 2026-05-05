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
        Schema::create('tmp_ventas', function (Blueprint $table) {
            $table->id();
            $table->integer('producto');
            $table->integer('familia');
            $table->string('name', 150);
            $table->decimal('price', 10, 4);
            $table->decimal('quantity', 10, 2);
            $table->integer('sucursal');
            $table->string('codebar', 50);
            $table->decimal('descuento', 10, 4);
            $table->decimal('total', 10, 4);
            $table->string('medida', 150);
            $table->decimal('limit', 10, 2);
            $table->decimal('descargar', 10, 2);
            $table->integer('uni');
            $table->integer('user');
            $table->integer('caja');
            $table->integer('empresa');
            $table->integer('esenario');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tmp_ventas');
    }
};
