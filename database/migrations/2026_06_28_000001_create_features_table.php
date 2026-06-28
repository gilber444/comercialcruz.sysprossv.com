<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('features', function (Blueprint $table) {
            $table->id();
            $table->string('version', 20)->index();
            $table->text('descripcion');
            $table->boolean('activo')->default(true);
            $table->boolean('produccion')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('features');
    }
};
