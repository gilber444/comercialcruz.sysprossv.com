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
        Schema::create('tmp_compras', function (Blueprint $table) {
            $table->id();
            $table->integer('producto');
            $table->string('name');
            $table->decimal('price', 10, 4);
            $table->decimal('quantity', 10, 2);
            $table->integer('sucursal');
            $table->integer('codebar');
            $table->date('vencimiento')->nullable();
            $table->decimal('total', 10, 4);
            $table->string('medida');
            $table->integer('ingreso');
            $table->integer('usuario');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tmp_compras');
    }
};
