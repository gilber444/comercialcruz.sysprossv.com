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
        Schema::create('catalagos_estructuras', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('catalago');
            $table->foreign('catalago')->references('id')->on('catalagos');
            $table->string('codigo', 10);
            $table->string('valores', 255);
            $table->string('dependencia', 10)->nullable();
            $table->string('estado', 20)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('catalagos_estructuras');
    }
};
