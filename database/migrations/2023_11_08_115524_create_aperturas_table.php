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
        Schema::create('aperturas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('caja');
            $table->foreign('caja')->references('id')->on('parametros');
            $table->unsignedBigInteger('sucursal');
            $table->foreign('sucursal')->references('id')->on('sucursales');
            $table->unsignedBigInteger('empresa');
            $table->foreign('empresa')->references('id')->on('empresas');
            $table->date('fechaApertura');
            $table->time('horaApertura');
            $table->decimal('inicio', 10, 4);
            $table->decimal('final', 10,4)->nullable();
            $table->date('FcierreApertura')->nullable();
            $table->time('HcierreApertura')->nullable();
            $table->enum('estado', ['Aperturado', 'Cerrado']);
            $table->unsignedBigInteger('cajero');
            $table->foreign('cajero')->references('id')->on('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('aperturas');
    }
};
