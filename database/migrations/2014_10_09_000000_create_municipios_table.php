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
        Schema::create('municipios', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 2);
            $table->string('municipio', 100);
            $table->unsignedBigInteger('departamento');
            $table->foreign('departamento')->references('id')->on('departamentos');
            $table->enum('status', ['Activo', 'Desactivado'])->default('Desactivado')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('municipios');
    }
};
