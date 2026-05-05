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
        Schema::create('control_numeraciones', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('empresa');
            $table->unsignedBigInteger('sucursal');
            $table->unsignedTinyInteger('tipoDte'); // 1 = FC, 2 = CC, etc.
            $table->unsignedBigInteger('correlativo')->default(1);
            $table->timestamps();

            $table->unique(['empresa', 'sucursal', 'tipoDte'], 'unique_num');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('control_numeraciones');
    }
};
