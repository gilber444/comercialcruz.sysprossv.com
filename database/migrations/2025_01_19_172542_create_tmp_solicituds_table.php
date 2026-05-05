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
        Schema::create('tmp_solicituds', function (Blueprint $table) {
            $table->id();
            $table->integer('producto');
            $table->integer('inventario')->nullable();
            $table->integer('sucursal')->nullable();
            $table->integer('medida')->nullable();
            $table->string('codebar', 50)->nullable();
            $table->string('name')->nullable();
            $table->string('unindad', 100)->nullable();
            $table->decimal('cantidad', 10,2)->nullable();
            $table->decimal('contenedor', 10, 2)->nullable();
            $table->decimal('solicita', 10, 2)->nullable();
            $table->decimal('costo', 10, 4);
            $table->integer('usuario');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tmp_solicituds');
    }
};
