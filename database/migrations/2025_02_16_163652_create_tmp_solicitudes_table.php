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
        Schema::create('tmp_solicitudes', function (Blueprint $table) {
            $table->id();
            $table->string('codebar')->nullable();
            $table->integer('producto')->nullable();
            $table->string('name')->nullable();
            $table->integer('cantidad')->nullable();
            $table->integer('costo')->nullable();
            $table->decimal('total', 10, 4)->nullable();
            $table->integer('unidad')->nullable();
            $table->string('medida')->nullable();
            $table->integer('limit')->nullable();
            $table->integer('descargar')->nullable();
            $table->integer('user')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tmp_solicitudes');
    }
};
