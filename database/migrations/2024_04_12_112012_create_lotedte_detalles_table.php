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
        Schema::create('lotedte_detalles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('lote');
            $table->foreign('lote')->references('id')->on('lotedtes');
            $table->unsignedBigInteger('dte');
            $table->foreign('dte')->references('id')->on('dtes');
            $table->string('estado');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lotedte_detalles');
    }
};
