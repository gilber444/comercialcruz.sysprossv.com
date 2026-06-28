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
        Schema::create('ajustes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sucursal');
            $table->foreign('sucursal')->references('id')->on('sucursales');
            $table->string('detalle', 255);
            $table->date('fecha');
            $table->string('tipo', 100)->nullable();
            $table->unsignedBigInteger('user');
            $table->foreign('user')->references('id')->on('users');
            $table->string('status', 100)->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ajustes');
    }
};
