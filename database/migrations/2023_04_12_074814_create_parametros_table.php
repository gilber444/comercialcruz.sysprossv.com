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
        Schema::create('parametros', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('empresa');
            $table->foreign('empresa')->references('id')->on('empresas');
            $table->unsignedBigInteger('sucursal');
            $table->foreign('sucursal')->references('id')->on('sucursales');
            $table->string('caja', 20);
            $table->string('token', 25);
            $table->string('full', 5)->nullable();
            $table->string('slip', 5)->nullable();
            $table->string('multiple', 5)->nullable();
            $table->string('centralizada', 5)->nullable();
            $table->string('ticket', 5)->nullable();
            $table->integer('tcorrelativo')->nullable();
            $table->string('tresolucion')->nullable();
            $table->string('tserie')->nullable();
            $table->string('consumidor', 5)->nullable();
            $table->integer('concorrelativo')->nullable();
            $table->string('conresolucion')->nullable();
            $table->string('conserie')->nullable();
            $table->string('credito', 5)->nullable();
            $table->integer('crecorrelativo')->nullable();
            $table->string('creresolucion')->nullable();
            $table->string('creserie')->nullable();
            $table->string('notacredito')->nullable();
            $table->integer('nccorrelativo')->nullable();
            $table->string('ncresolucion')->nullable();
            $table->string('ncserie')->nullable();
            $table->string('notadebito')->nullable();
            $table->integer('ndcorrelativo')->nullable();
            $table->string('ndresolucion')->nullable();
            $table->string('ndserie')->nullable();
            $table->string('cotizacion', 5)->nullable();
            $table->integer('cocorrelativo')->nullable();
            $table->integer('tasa')->nullable();
            $table->decimal('ventamin', 10,4)->nullable();
            $table->enum('dte', ['Si', 'No'])->default('No');
            $table->enum('dteAutomatico', ['Si', 'No'])->default('No');
            $table->string('tiquedte', 5)->nullable();
            $table->string('estado', 50)->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parametros');
    }
};
