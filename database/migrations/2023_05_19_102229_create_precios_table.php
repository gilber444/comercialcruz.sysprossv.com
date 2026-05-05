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
        Schema::create('precios', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('producto');
            $table->foreign('producto')->references('id')->on('productos');
            $table->unsignedBigInteger('familia');
            $table->foreign('familia')->references('id')->on('familias');
            $table->unsignedBigInteger('medida');
            $table->foreign('medida')->references('id')->on('medidas');
            $table->string('codebar', 255);
            $table->decimal('cantidad', 10,2);
            $table->decimal('presentacion', 10,4);
            $table->decimal('costosiva', 10, 4);
            $table->decimal('costociva', 10, 4);
            $table->decimal('utilidad', 10, 4);
            $table->decimal('pventasiva', 10, 4);
            $table->decimal('pvventa', 10, 4);
            $table->enum('escala', ['Si', 'No'])->default('No');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('precios');
    }
};
