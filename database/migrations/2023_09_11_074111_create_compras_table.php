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
        Schema::create('compras', function (Blueprint $table) {
            $table->id();
            $table->integer('numero');
            $table->unsignedBigInteger('tipo');
            $table->foreign('tipo')->references('id')->on('tipo_compras');
            $table->string('correlativo', 50);
            $table->string('serie', 50)->nullable();
            $table->dateTime('fecha');
            $table->enum('condi_pago', ['Credito', 'Contado'])->default('Contado');
            $table->string('vendedor', 150)->nullable();
            $table->string('estado', 50);
            $table->dateTime('fechaPAgo')->nullable();
            $table->unsignedBigInteger('proveedor');
            $table->foreign('proveedor')->references('id')->on('proveedores');
            $table->unsignedBigInteger('user');
            $table->foreign('user')->references('id')->on('users');
            $table->unsignedBigInteger('sucursal');
            $table->foreign('sucursal')->references('id')->on('sucursales');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('compras');
    }
};
