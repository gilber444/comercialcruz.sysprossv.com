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
        Schema::create('solicitudes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('origen');
            $table->foreign('origen')->references('id')->on('sucursales');
            $table->unsignedBigInteger('destino');
            $table->foreign('destino')->references('id')->on('sucursales');
            $table->integer('numero');
            $table->dateTime('fecha');
            $table->string('detalle')->nullable();
            $table->unsignedBigInteger('solicitante');
            $table->foreign('solicitante')->references('id')->on('users');
            $table->string('estado', 50);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('solicitudes');
    }
};
