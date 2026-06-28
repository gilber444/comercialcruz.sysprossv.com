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
        Schema::create('productos', function (Blueprint $table) {
            $table->id();
            $table->string('codebar1', 45)->nullable();
            $table->string('codebar2', 45)->nullable();
            $table->string('codebar3', 45)->nullable();
            $table->string('codealternativo', 45)->nullable();
            $table->string('nombreProducto', 255);
            $table->unsignedBigInteger('categoria');
            $table->foreign('categoria')->references('id')->on('categorias');
            $table->unsignedBigInteger('familia');
            $table->foreign('familia')->references('id')->on('familias');
            $table->unsignedBigInteger('medida');
            $table->foreign('medida')->references('id')->on('medidas');
            $table->string('proveedor1', 200)->nullable();
            $table->string('proveedor2', 200)->nullable();
            $table->string('proveedor3', 200)->nullable();
            $table->string('activo', 5)->nullable();
            $table->string('exento', 5)->nullable();
            $table->string('caja', 5)->nullable();
            $table->string('fraccionario', 5)->nullable();
            $table->unsignedBigInteger('medidamh');
            $table->foreign('medidamh')->references('id')->on('unidad_medidas');
            $table->integer('contenedor', 11)->nullable();
            $table->integer('maximo', 11)->nullable();
            $table->integer('minimo', 11)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('productos');
    }
};
