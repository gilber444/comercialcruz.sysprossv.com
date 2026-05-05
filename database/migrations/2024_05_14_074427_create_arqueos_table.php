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
        Schema::create('arqueos', function (Blueprint $table) {
            $table->id();
            $table->integer('numero');
            $table->date('fecha');
            $table->time('hora');
            $table->unsignedBigInteger('caja');
            $table->foreign('caja')->references('id')->on('parametros');
            $table->unsignedBigInteger('sucursal');
            $table->foreign('sucursal')->references('id')->on('sucursales');
            $table->unsignedBigInteger('empresa');
            $table->foreign('empresa')->references('id')->on('empresas');
            $table->unsignedBigInteger('cajero');
            $table->foreign('cajero')->references('id')->on('users');
            $table->enum('tipo', ['X', 'Z'])->default('X');
            $table->decimal('efectivo', 10,4)->nullable();
            $table->decimal('tarjeta', 10, 4)->nullable();
            $table->decimal('cheque', 10, 4)->nullable();
            $table->decimal('credito', 10, 4)->nullable();
            $table->decimal('subtotalPagos', 10, 4)->nullable();
            $table->decimal('devoluciones', 10, 4)->nullable();
            $table->decimal('anulaciones')->nullable();
            $table->decimal('remesas', 10, 4)->nullable();
            $table->decimal('percepcion', 10, 4)->nullable();
            $table->decimal('sumaTotales', 10, 4)->nullable();
            $table->integer('ticketDesde')->nullable();
            $table->integer('ticketHasta')->nullable();
            $table->decimal('gravadosT', 10,4)->nullable();
            $table->decimal('ivaT', 10, 4)->nullable();
            $table->decimal('subT', 10, 4)->nullable();
            $table->decimal('totalT', 10, 4)->nullable();
            $table->integer('consumidorDesde')->nullable();
            $table->integer('consumidorHasta')->nullable();
            $table->decimal('gravadosCon', 10,4)->nullable();
            $table->decimal('ivaCon', 10, 4)->nullable();
            $table->decimal('subCon', 10, 4)->nullable();
            $table->decimal('totalCon', 10, 4)->nullable();
            $table->integer('CreDesde')->nullable();
            $table->integer('CreHasta')->nullable();
            $table->decimal('gravadosCre', 10,4)->nullable();
            $table->decimal('ivaCre', 10, 4)->nullable();
            $table->decimal('subCre', 10, 4)->nullable();
            $table->decimal('totalCre', 10, 4)->nullable();
            $table->string('dteDesde', 255)->nullable();
            $table->string('dteHasta', 255)->nullable();
            $table->decimal('gravadosDTE', 10,4)->nullable();
            $table->decimal('ivaDTE', 10, 4)->nullable();
            $table->decimal('subDTE', 10, 4)->nullable();
            $table->decimal('totalDTE', 10, 4)->nullable();
            $table->integer('creditosDesde')->nullable();
            $table->integer('creditosHasta')->nullable();
            $table->decimal('gravadosCredi', 10,4)->nullable();
            $table->decimal('ivaCredi', 10, 4)->nullable();
            $table->decimal('subCredi', 10, 4)->nullable();
            $table->decimal('totalCredi', 10, 4)->nullable();
            $table->decimal('totalGeneral', 10, 4)->nullable();
            $table->decimal('ivaGeneral', 10,4)->nullable();
            $table->decimal('subGeneral', 10, 4)->nullable();
            $table->decimal('totalPercepcion', 10, 4 )->nullable();
            $table->decimal('totalGlobal', 10, 4)->nullable();
            $table->decimal('totalEfectivo', 10, 4)->nullable();
            $table->decimal('diferencia', 10, 4)->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('arqueos');
    }
};
