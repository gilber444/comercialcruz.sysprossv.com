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
        Schema::create('bitacora_sincronizacions', function (Blueprint $table) {
            $table->id();
            $table->uuid('sincro_id')->unique();
            $table->string('tabla');              // productos, ventas, etc.
            $table->enum('origen', ['vps', 'local']);
            $table->enum('destino', ['vps', 'local']);
            $table->string('sucursal')->nullable(); // si aplica
            $table->integer('registros')->default(0);
            $table->integer('insertados')->default(0);  // nuevos registros
            $table->integer('actualizados')->default(0);// registros modificados
            $table->text('mensaje')->nullable();  // para errores o info adicional
            $table->timestamp('fecha');
            $table->softDeletes();           // fecha real de ejecución
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bitacora_sincronizacions');
    }
};
