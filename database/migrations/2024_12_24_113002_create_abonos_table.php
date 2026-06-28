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
        Schema::create('abonos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('empresa');
            $table->foreign('empresa')->references('id')->on('empresas');
            $table->unsignedBigInteger('sucursal');
            $table->foreign('sucursal')->references('id')->on('sucursales');
            $table->unsignedBigInteger('credito');
            $table->foreign('credito')->references('id')->on('creditos');
            $table->integer('correlativo');
            $table->date('fecha');
            $table->time('hora');
            $table->decimal('monto', 15, 4);
            $table->unsignedBigInteger('user');
            $table->foreign('user')->references('id')->on('users');
            $table->enum('estado', ['pendiente', 'pagado'])->default('pendiente');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('abonos');
    }
};
