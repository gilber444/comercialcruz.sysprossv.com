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
        Schema::create('envio_tmps', function (Blueprint $table) {
            $table->id();
            $table->integer('usuario');
            $table->integer('empresa');
            $table->integer('sucursal');
            $table->integer('caja');
            $table->integer('envio');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('envio_tmps');
    }
};
