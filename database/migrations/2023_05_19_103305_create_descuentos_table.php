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
        Schema::create('descuentos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('producto');
            $table->foreign('producto')->references('id')->on('productos');
            $table->unsignedBigInteger('precio');
            $table->foreign('precio')->references('id')->on('precios');
            $table->date('inicio');
            $table->date('fin');
            $table->decimal('descuento', 10, 2);
            $table->decimal('valor', 10, 2);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('descuentos');
    }
};
