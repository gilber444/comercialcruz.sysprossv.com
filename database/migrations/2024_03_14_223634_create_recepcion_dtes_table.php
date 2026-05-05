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
        Schema::create('recepcion_dtes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('dte');
            $table->foreign('dte')->references('id')->on('dtes');
            $table->date('fechaRecepcion');
            $table->time('horaRecepcion');
            $table->string('selloRecibido', 255)->nullable();
            $table->timestamp('fhProcesamiento');
            $table->string('estado', 50);
            $table->string('observaciones', 255)->nullable();
            $table->json('josn')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recepcion_dtes');
    }
};
