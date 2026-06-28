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
        Schema::create('sucursal_descuentos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('descuento');
            $table->foreign('descuento')->references('id')->on('descuentos');
            $table->unsignedBigInteger('sucursal');
            $table->foreign('sucursal')->references('id')->on('sucursales');
            $table->boolean('activo')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sucursal_descuentos');
    }
};
