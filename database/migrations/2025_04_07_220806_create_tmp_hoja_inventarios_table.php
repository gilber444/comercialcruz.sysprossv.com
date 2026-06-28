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
        Schema::create('tmp_hoja_inventarios', function (Blueprint $table) {
            $table->id();
            $table->integer('producto');
            $table->integer('hoja');
            $table->integer('sucursal');
            $table->string('name');
            $table->string('codebar')->nullable();
            $table->string('medida');
            $table->decimal('existencia', 10, 2)->nullable();
            $table->decimal('conteoFisico', 10, 4);
            $table->decimal('cantidad', 10, 4);
            $table->decimal('diferencia', 10, 2);
            $table->decimal('limit', 10, 4);
            $table->decimal('costo', 10, 4);
            $table->decimal('total', 10, 4);
            $table->integer('user');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tmp_hoja_inventarios');
    }
};
